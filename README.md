# CineClean

CineClean is a Laravel + Blade movie library deduplication app for SMB shares.
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
TMDB_API_KEY=your-api-key
TMDB_TOKEN=your-read-token
CINECLEAN_ALLOW_DELETE=false
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
|                    Laravel App (CineClean)              |
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
- Deletion is disabled by default (`CINECLEAN_ALLOW_DELETE=false`).
- To allow manual deletion from the UI, set `CINECLEAN_ALLOW_DELETE=true`.
- File deletion only happens from the web UI after explicit confirmation.
- TMDB responses are cached for 7 days.
