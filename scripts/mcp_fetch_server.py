#!/usr/bin/env python3
"""Minimal local MCP stdio server for URL fetch.

Implements enough of MCP to support tools/call for tool name "fetch".
Returns markdown-ish link output so existing PHP parsing logic can extract titles.
"""

from __future__ import annotations

import html
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser
from typing import Any


USER_AGENT = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/122.0.0.0 Safari/537.36 moviesanalyzer-mcp/1.0"
)


class LinkParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.in_anchor = False
        self.current_href = ""
        self.current_text_parts: list[str] = []
        self.links: list[tuple[str, str]] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        if tag.lower() != "a":
            return
        href = ""
        for key, value in attrs:
            if key.lower() == "href" and value is not None:
                href = value
                break
        if href == "":
            return
        self.in_anchor = True
        self.current_href = href
        self.current_text_parts = []

    def handle_data(self, data: str) -> None:
        if not self.in_anchor:
            return
        value = data.strip()
        if value != "":
            self.current_text_parts.append(value)

    def handle_endtag(self, tag: str) -> None:
        if tag.lower() != "a" or not self.in_anchor:
            return
        title = " ".join(self.current_text_parts).strip()
        self.links.append((self.current_href, title))
        self.in_anchor = False
        self.current_href = ""
        self.current_text_parts = []


def write_message(payload: dict[str, Any]) -> None:
    sys.stdout.write(json.dumps(payload, ensure_ascii=False) + "\n")
    sys.stdout.flush()


def respond_error(message_id: Any, code: int, message: str) -> None:
    write_message(
        {
            "jsonrpc": "2.0",
            "id": message_id,
            "error": {
                "code": code,
                "message": message,
            },
        }
    )


def normalize_google_redirect(url: str) -> str:
    try:
        parsed = urllib.parse.urlparse(url)
    except ValueError:
        return url

    host = (parsed.hostname or "").lower()
    if "google." not in host:
        return url

    if parsed.path not in ("/url", "/imgres"):
        return url

    params = urllib.parse.parse_qs(parsed.query)
    target = ""
    if "url" in params and params["url"]:
        target = params["url"][0]
    elif "q" in params and params["q"]:
        target = params["q"][0]

    if target == "":
        return url

    return urllib.parse.unquote(target)


def is_candidate_url(url: str) -> bool:
    try:
        parsed = urllib.parse.urlparse(url)
    except ValueError:
        return False

    if parsed.scheme not in ("http", "https"):
        return False

    host = (parsed.hostname or "").lower()
    if host == "":
        return False

    blocked_hosts = {
        "google.com",
        "www.google.com",
        "support.google.com",
        "accounts.google.com",
        "policies.google.com",
        "webcache.googleusercontent.com",
    }
    if host in blocked_hosts:
        return False

    return not host.startswith("maps.google.")


def strip_tags(value: str) -> str:
    text = re.sub(r"<[^>]+>", " ", value, flags=re.IGNORECASE | re.DOTALL)
    text = html.unescape(text)
    text = re.sub(r"\s+", " ", text, flags=re.UNICODE).strip()
    return text


def fetch_url(url: str) -> str:
    request = urllib.request.Request(
        url=url,
        headers={
            "User-Agent": USER_AGENT,
            "Accept-Language": "en-US,en;q=0.9,ru;q=0.8",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        },
        method="GET",
    )

    with urllib.request.urlopen(request, timeout=20) as response:  # nosec B310
        raw = response.read()
        content_type = response.headers.get("Content-Type", "")
        charset = "utf-8"
        match = re.search(r"charset=([^\s;]+)", content_type, flags=re.IGNORECASE)
        if match:
            charset = match.group(1).strip()
        try:
            return raw.decode(charset, errors="replace")
        except LookupError:
            return raw.decode("utf-8", errors="replace")


def extract_markdown_links(html_text: str, max_results: int) -> str:
    parser = LinkParser()
    parser.feed(html_text)

    seen: set[str] = set()
    lines: list[str] = []

    for href, raw_title in parser.links:
        normalized_href = normalize_google_redirect(html.unescape(href))
        if not is_candidate_url(normalized_href):
            continue

        dedupe_key = normalized_href.lower()
        if dedupe_key in seen:
            continue
        seen.add(dedupe_key)

        title = strip_tags(raw_title)
        if title == "":
            host = urllib.parse.urlparse(normalized_href).hostname or normalized_href
            title = host

        lines.append(f"[{title}]({normalized_href})")

        if len(lines) >= max_results:
            break

    if lines:
        return "\n".join(lines)

    text = strip_tags(html_text)
    if text == "":
        return ""

    return text[:8000]


def handle_fetch(arguments: dict[str, Any]) -> str:
    url = str(arguments.get("url", "")).strip()
    if url == "":
        raise ValueError('Argument "url" is required.')

    max_length_raw = arguments.get("max_length", 18000)
    try:
        max_length = int(max_length_raw)
    except (ValueError, TypeError):
        max_length = 18000
    max_length = max(1000, min(max_length, 50000))

    html_text = fetch_url(url)
    markdown_output = extract_markdown_links(html_text, max_results=12)

    if len(markdown_output) > max_length:
        return markdown_output[:max_length]

    return markdown_output


def handle_request(message: dict[str, Any]) -> None:
    message_id = message.get("id")
    method = str(message.get("method", ""))

    if method == "initialize":
        write_message(
            {
                "jsonrpc": "2.0",
                "id": message_id,
                "result": {
                    "protocolVersion": "2024-11-05",
                    "capabilities": {
                        "tools": {},
                    },
                    "serverInfo": {
                        "name": "moviesanalyzer-local-fetch",
                        "version": "1.0.0",
                    },
                },
            }
        )
        return

    if method == "tools/call":
        params = message.get("params")
        if not isinstance(params, dict):
            respond_error(message_id, -32602, "Invalid params.")
            return

        tool_name = str(params.get("name", "")).strip()
        if tool_name not in ("fetch", "mcp-server-fetch"):
            respond_error(message_id, -32601, f'Unknown tool "{tool_name}".')
            return

        arguments = params.get("arguments")
        if not isinstance(arguments, dict):
            arguments = {}

        try:
            text = handle_fetch(arguments)
        except urllib.error.URLError as exception:
            respond_error(message_id, -32001, f"Network error: {exception}")
            return
        except Exception as exception:  # noqa: BLE001
            respond_error(message_id, -32002, str(exception))
            return

        write_message(
            {
                "jsonrpc": "2.0",
                "id": message_id,
                "result": {
                    "content": [
                        {
                            "type": "text",
                            "text": text,
                        }
                    ]
                },
            }
        )
        return

    if method == "notifications/initialized":
        return

    if message_id is not None:
        respond_error(message_id, -32601, f'Unknown method "{method}".')


def main() -> int:
    for raw_line in sys.stdin:
        line = raw_line.strip()
        if line == "":
            continue
        try:
            message = json.loads(line)
        except json.JSONDecodeError:
            continue
        if not isinstance(message, dict):
            continue
        handle_request(message)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
