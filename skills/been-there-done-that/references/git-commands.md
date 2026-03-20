# Git Commands Reference

Used in Phase 0 of the been-there-done-that workflow.

## Table of Contents
- [Session Extraction](#session-extraction)
- [Project Name Extraction](#project-name-extraction)
- [Diff Stats for Selected Session](#diff-stats-for-selected-session)
- [Branch Info](#branch-info)
- [Stack Inference from File Extensions](#stack-inference-from-file-extensions)

---

## Session Extraction

### Get all commits with timestamps

```bash
# Full log: unix_timestamp|hash|subject
git -C <repo_path> log --format="%at|%H|%s" 2>/dev/null
```

Output example:
```
1740038400|a1b2c3d|feat: add WebSocket heartbeat
1739952000|e4f5a6b|fix: auth token refresh
1737360000|c7d8e9f|initial setup
```

Gap between first and second commit: `1740038400 - 1739952000 = 86400s (1 day)` → same session
Gap between second and third commit: `1739952000 - 1737360000 = 2592000s (30 days)` → session boundary

### Parse sessions (bash one-liner)

```bash
git -C <repo_path> log --format="%at|%H|%s" | awk -F'|' '
BEGIN { session=1; prev_ts=0 }
{
  ts=$1; hash=$2; msg=$3
  if (prev_ts > 0 && (prev_ts - ts) > 259200) {
    print "---BOUNDARY---"
    session++
  }
  print session "|" ts "|" hash "|" msg
  prev_ts = ts
}
'
```

---

## Project Name Extraction

```bash
# From remote URL (handles https and ssh formats)
git -C <repo_path> remote get-url origin 2>/dev/null \
  | sed 's/.*\///' \
  | sed 's/\.git$//'

# Fallback: use folder name
basename <repo_path>
```

### Name cleaning (pseudo-code)

```
raw = output from above

prefixes = ["frontend-", "backend-", "api-", "service-", "mobile-", "web-", "app-"]
suffixes = ["-main", "-dev", "-staging", "-master", "-prod", "-v1", "-v2", "-branch"]

for prefix in prefixes:
    if raw.startswith(prefix): raw = raw[len(prefix):]

for suffix in suffixes:
    if raw.endswith(suffix): raw = raw[:-len(suffix)]

clean = raw.replace("-", " ").title()
```

---

## Diff Stats for Selected Session

Once user confirms which session (by commit hashes):

```bash
# Files changed + insertions + deletions summary
git -C <repo_path> diff --stat <oldest_hash>..<newest_hash> 2>/dev/null

# Shortened: just the summary line
git -C <repo_path> diff --shortstat <oldest_hash>..<newest_hash> 2>/dev/null
# Output: "12 files changed, 847 insertions(+), 203 deletions(-)"

# List of unique file extensions touched (for stack inference)
git -C <repo_path> diff --name-only <oldest_hash>..<newest_hash> 2>/dev/null \
  | sed 's/.*\.//' | sort -u
```

---

## Branch Info

```bash
git -C <repo_path> branch --show-current 2>/dev/null

# If detached HEAD, fallback:
git -C <repo_path> rev-parse --abbrev-ref HEAD 2>/dev/null
```

---

## Stack Inference from File Extensions

Map file extensions to tech labels for the **Stack** field:

```
.rs          → Rust
.ts .tsx     → TypeScript
.js .jsx     → JavaScript
.py          → Python
.go          → Go
.sql         → SQL
.toml        → Cargo / Config
.yaml .yml   → Config / CI
.md          → Documentation
.html .css   → Frontend
Dockerfile   → Docker
.sh          → Shell
```

Use only extensions that appear in the session diff. Deduplicate. List in order of frequency.
