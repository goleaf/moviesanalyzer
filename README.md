# moviesanalyzer

moviesanalyzer is a Laravel + Blade movie library deduplication app for SMB shares.
It scans movie files on your NAS, matches them against TMDB, groups duplicates by TMDB movie ID, and lets you review duplicates before any manual delete action.

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) or MySQL
- `smbclient` installed

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
TMDB_MCP_UV_CACHE_DIR=/tmp/moviesanalyzer-mcp-uv-cache
MOVIESANALYZER_ALLOW_DELETE=false
```

If `smbclient` reports `Can't load ... smb.conf`, set `SMBCLIENT_CONFIG_FILE=/dev/null` or a valid `smb.conf` path.
`SMB_BACKEND=auto` uses `icewind/smb` first, then falls back to CLI.
For Homebrew/macOS reliability, set `SMB_BACKEND=cli`.

## TMDB MCP Integration (MCP Market)

This project supports the TMDB MCP server listed on MCP Market:
`https://mcpmarket.com/server/tmdb`

### Required tooling

- Node.js 18+
- pnpm 10.7+
- `tsx` (used via `pnpm exec tsx`)
- TMDB API key

### Recommended setup

```bash
git clone https://github.com/leonardogilrodriguez/mcp-tmdb.git /absolute/path/to/mcp-tmdb
pnpm --dir /absolute/path/to/mcp-tmdb install
```

Then set these values in `.env`:

```env
TMDB_PROVIDER=mcp_with_http_fallback
TMDB_MCP_ENABLED=true
TMDB_MCP_HTTP_FALLBACK=true
TMDB_MCP_COMMAND="pnpm --dir /absolute/path/to/mcp-tmdb exec tsx main.ts"
TMDB_MCP_TIMEOUT_SECONDS=30
TMDB_MCP_SEARCH_TOOLS=search_movies,search_movie,movie_search,find_movie,two_actors_on_screen,two_people,two_movies,filmography_actor_genre,filmography_crew_genre
TMDB_MCP_MOVIE_TOOLS=get_movie_details,get_movie,movie_details
TMDB_MCP_RESOURCE_URIS=tmdb://movie/{id},tmdb:///movie/{id},tmdb://movie/{id}?language={language}
TMDB_MCP_CACHE_HOURS=12
TMDB_MCP_UV_CACHE_DIR=/tmp/moviesanalyzer-mcp-uv-cache
```

`mcp_with_http_fallback` keeps searches working even if the MCP process is unavailable or returns no usable movie rows.

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
