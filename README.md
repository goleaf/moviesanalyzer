# moviesanalyzer

moviesanalyzer is a Laravel + Blade movie library deduplication app for SMB shares.
It scans movie files on your NAS, matches them against TMDB, groups duplicates by TMDB movie ID, and lets you review duplicates before any manual delete action.

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) or MySQL
- `smbclient` installed
- A free MCP fetch server command for Google research (default: `uvx mcp-server-fetch`)

Install `smbclient`:

```bash
# macOS
brew install samba

# Ubuntu / Debian
apt install smbclient
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Open `http://localhost:8000` and click **Scan Library**.

## Environment Variables

Configure SMB and TMDB in `.env`:

```env
SMB_HOST=meganas
SMB_SHARE=Video
SMB_PATH=Movies
SMB_USERNAME=andrej
SMB_PASSWORD=your-password
SMB_BACKEND=auto
SMB_WORKGROUP=
SMBCLIENT_BIN=smbclient
SMBCLIENT_CONFIG_FILE=
SMB_TIMEOUT_SECONDS=180
TMDB_API_KEY=your-api-key
TMDB_TOKEN=your-read-token
MOVIESANALYZER_ALLOW_DELETE=false
```

If `smbclient` reports `Can't load ... smb.conf`, set `SMBCLIENT_CONFIG_FILE=/dev/null` or a valid `smb.conf` path.
`SMB_BACKEND=auto` uses `icewind/smb` first, then falls back to CLI.
For Homebrew/macOS reliability, set `SMB_BACKEND=cli`.

Google research for unmatched titles uses MCP (no Google API keys). Default `.env` values:

```env
GOOGLE_ASSIST_PROVIDER=mcp_google_fetch
GOOGLE_ASSIST_MCP_COMMAND="uvx mcp-server-fetch"
GOOGLE_ASSIST_MCP_TOOL=fetch
GOOGLE_ASSIST_MCP_TIMEOUT_SECONDS=25
GOOGLE_ASSIST_MCP_MAX_LENGTH=18000
GOOGLE_ASSIST_MCP_UV_CACHE_DIR=/tmp/moviesanalyzer-mcp-uv-cache
GOOGLE_ASSIST_GOOGLE_SEARCH_URL=https://www.google.com/search
```

## CLI Scan

Run full scan from terminal:

```bash
php artisan movies:scan
```

Example output:

```text
Scanning smb://meganas/Video/Movies/...
[████████████░░░░░░░░] 62% Matching: matrix.mkv
Done. 847 files | 143 duplicate groups | 312.40 GB wasted
```

## Architecture

```text
+----------------------+        +-------------------------+
|  SMB Share           |        |  TMDB API               |
|  smb://.../Movies    |        |  /search/movie, /movie  |
+----------+-----------+        +------------+------------+
           |                                 |
           v                                 v
+---------------------------------------------------------+
|                 Laravel App (moviesanalyzer)             |
|                                                         |
|  SmbService -> FilenameParser -> TmdbService            |
|         \            |              /                   |
|          \           v             /                    |
|           +---- ScanMovieLibraryAction ----+            |
|                         |                   |            |
|                         v                   |            |
|                  movie_files table          |            |
|                  scan_logs table            |            |
|                         |                   |            |
|                         v                   |            |
|          Blade UI: Dashboard / Duplicates / Movies      |
|          SSE Progress + Manual Match + Confirm Delete   |
+---------------------------------------------------------+
```

## Safety

- No automatic delete operations are performed.
- Deletion is disabled by default (`MOVIESANALYZER_ALLOW_DELETE=false`).
- To allow manual deletion from the UI, set `MOVIESANALYZER_ALLOW_DELETE=true`.
- File deletion only happens from the web UI after explicit confirmation.
- TMDB responses are cached for 7 days.
