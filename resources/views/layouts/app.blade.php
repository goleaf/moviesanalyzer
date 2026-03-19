<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CineClean')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --card: #12121a;
            --border: #1e1e2e;
            --gold: #f5a623;
            --text: #e8e8f0;
            --muted: #6b6b8a;
            --danger: #e53e3e;
            --success: #38a169;
            --warning: #d69e2e;
        }

        * {
            font-family: 'DM Sans', sans-serif;
        }

        .font-cinema {
            font-family: 'Playfair Display', serif;
        }

        body {
            background: radial-gradient(circle at 15% 10%, #111126 0%, var(--bg) 55%);
            color: var(--text);
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='160' height='160' filter='url(%23n)' opacity='0.4'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
            z-index: 9999;
        }

        .card {
            background: rgba(18, 18, 26, 0.92);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
        }

        .text-muted {
            color: var(--muted);
        }

        .bg-card {
            background-color: var(--card);
        }

        .border-cine {
            border-color: var(--border);
        }

        .link-active {
            background: rgba(245, 166, 35, 0.15);
            border-color: rgba(245, 166, 35, 0.4);
            color: var(--gold);
        }
    </style>
    @stack('head')
</head>
<body>
<div class="min-h-screen flex">
    <aside class="w-72 border-r border-cine bg-black/30 backdrop-blur-md p-6 hidden lg:flex flex-col gap-8">
        <div>
            <p class="font-cinema text-3xl tracking-wide" style="color: var(--gold);">CINECLEAN</p>
            <p class="text-sm text-muted mt-1">Movie library deduplication</p>
        </div>

        <nav class="space-y-3">
            <a href="{{ route('cineclean.dashboard') }}" class="block px-4 py-3 rounded-xl border border-transparent transition {{ request()->routeIs('cineclean.dashboard') ? 'link-active' : 'hover:border-cine hover:bg-white/5' }}">Dashboard</a>
            <a href="{{ route('cineclean.duplicates.index') }}" class="flex items-center justify-between px-4 py-3 rounded-xl border border-transparent transition {{ request()->routeIs('cineclean.duplicates.*') ? 'link-active' : 'hover:border-cine hover:bg-white/5' }}">
                <span>Duplicates</span>
                <span class="text-xs px-2 py-1 rounded-full bg-white/10">{{ $navDuplicateCount ?? 0 }}</span>
            </a>
            <a href="{{ route('cineclean.movies.index') }}" class="block px-4 py-3 rounded-xl border border-transparent transition {{ request()->routeIs('cineclean.movies.*') ? 'link-active' : 'hover:border-cine hover:bg-white/5' }}">All Movies</a>
            <a href="{{ route('cineclean.unmatched.index') }}" class="block px-4 py-3 rounded-xl border border-transparent transition {{ request()->routeIs('cineclean.unmatched.*') ? 'link-active' : 'hover:border-cine hover:bg-white/5' }}">Unmatched</a>
        </nav>

        <p class="text-xs text-muted mt-auto">No files are deleted automatically. Deletion is manual and always confirmed.</p>
    </aside>

    <main class="flex-1 p-4 md:p-8">
        <header class="mb-8 flex items-center justify-between gap-4">
            <div>
                <p class="font-cinema text-4xl md:text-5xl leading-none" style="color: var(--gold);">CINECLEAN</p>
                <p class="text-muted mt-2">Review duplicates before any delete action.</p>
            </div>
            <a href="{{ route('cineclean.dashboard') }}" class="lg:hidden rounded-lg px-4 py-2 border border-cine bg-card text-sm">Menu Home</a>
        </header>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-cine bg-card px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

<div id="toast" class="fixed bottom-6 right-6 hidden z-50"></div>

<script>
    window.CineClean = {
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        formatBytes(bytes) {
            if (bytes < 1024) {
                return `${bytes} B`;
            }

            const units = ['KB', 'MB', 'GB', 'TB'];
            let value = bytes / 1024;

            for (const unit of units) {
                if (value < 1024 || unit === 'TB') {
                    return `${value.toFixed(2)} ${unit}`;
                }

                value /= 1024;
            }

            return `${value.toFixed(2)} TB`;
        },
        toast(message, type = 'success') {
            const el = document.getElementById('toast');
            const colors = {
                success: 'bg-green-700/90 border-green-500',
                error: 'bg-red-700/90 border-red-500',
                info: 'bg-slate-800/95 border-slate-600'
            };

            el.className = `fixed bottom-6 right-6 z-50 rounded-xl border px-4 py-3 text-sm shadow-2xl ${colors[type] ?? colors.info}`;
            el.textContent = message;
            el.classList.remove('hidden');

            setTimeout(() => {
                el.classList.add('hidden');
            }, 3000);
        }
    };
</script>
@stack('scripts')
</body>
</html>
