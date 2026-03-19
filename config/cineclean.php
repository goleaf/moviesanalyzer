<?php

return [
    'smb' => [
        'host' => env('SMB_HOST', 'meganas'),
        'share' => env('SMB_SHARE', 'Video'),
        'path' => env('SMB_PATH', 'Movies'),
        'username' => env('SMB_USERNAME', ''),
        'password' => env('SMB_PASSWORD', ''),
        'binary' => env('SMBCLIENT_BIN', 'smbclient'),
        'video_extensions' => [
            'mkv',
            'mp4',
            'avi',
            'mov',
            'wmv',
            'm4v',
            'ts',
            'mpg',
            'mpeg',
            'divx',
            'xvid',
            'flv',
            'webm',
        ],
    ],
    'tmdb' => [
        'api_key' => env('TMDB_API_KEY'),
        'token' => env('TMDB_TOKEN'),
        'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
        'cache_days' => (int) env('TMDB_CACHE_DAYS', 7),
        'requests_per_window' => (int) env('TMDB_RATE_MAX_REQUESTS', 40),
        'window_seconds' => (int) env('TMDB_RATE_WINDOW_SECONDS', 10),
        'match_threshold' => (float) env('TMDB_MATCH_THRESHOLD', 0.75),
        'uncertain_threshold' => (float) env('TMDB_UNCERTAIN_THRESHOLD', 0.55),
    ],
    'scan' => [
        'progress_cache_key' => 'cineclean.scan.progress',
        'start_flag_cache_key' => 'cineclean.scan.requested',
        'cache_ttl_minutes' => 360,
    ],
    'files' => [
        'allow_delete' => (bool) env('CINECLEAN_ALLOW_DELETE', true),
    ],
];
