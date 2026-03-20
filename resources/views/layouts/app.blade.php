<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'moviesanalyzer')</title>
    @llmReady
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (! app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    @stack('head')
</head>
<body class="h-full">
<div class="relative min-h-screen xl:grid xl:grid-cols-[18rem_1fr]">
    <aside class="hidden border-r border-cine bg-slate-950/65 px-5 py-6 xl:flex xl:flex-col xl:gap-8">
        <div>
            <p class="font-cinema text-3xl tracking-tight text-amber-300">MOVIESANALYZER</p>
            <p class="mt-1 text-xs uppercase tracking-[0.22em] text-slate-400">Livewire 4 + Tailwind 4</p>
        </div>

        <nav class="space-y-2 text-sm">
            <a href="{{ route('cineclean.dashboard') }}" class="block rounded-xl border px-4 py-3 transition {{ request()->routeIs('cineclean.dashboard') ? 'link-active' : 'border-transparent hover:border-cine hover:bg-slate-800/50' }}">
                Dashboard
            </a>
            <a href="{{ route('cineclean.movies.index') }}" class="block rounded-xl border px-4 py-3 transition {{ request()->routeIs('cineclean.movies.*') ? 'link-active' : 'border-transparent hover:border-cine hover:bg-slate-800/50' }}">
                Movies
            </a>
            <a href="{{ route('cineclean.duplicates.index') }}" class="flex items-center justify-between rounded-xl border px-4 py-3 transition {{ request()->routeIs('cineclean.duplicates.*') ? 'link-active' : 'border-transparent hover:border-cine hover:bg-slate-800/50' }}">
                <span>Duplicates</span>
                <span class="rounded-full border border-cine px-2 py-0.5 text-[11px] text-slate-300">{{ $navDuplicateCount ?? 0 }}</span>
            </a>
            <a href="{{ route('cineclean.unmatched.index') }}" class="block rounded-xl border px-4 py-3 transition {{ request()->routeIs('cineclean.unmatched.*') ? 'link-active' : 'border-transparent hover:border-cine hover:bg-slate-800/50' }}">
                Unmatched
            </a>
            <a href="{{ route('cineclean.rules.index') }}" class="block rounded-xl border px-4 py-3 transition {{ request()->routeIs('cineclean.rules.*') ? 'link-active' : 'border-transparent hover:border-cine hover:bg-slate-800/50' }}">
                Parser Rules
            </a>
        </nav>

        <div class="mt-auto card p-4 text-xs text-muted">
            All destructive actions require explicit confirmation and never run automatically.
        </div>
    </aside>

    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-40 border-b border-cine bg-slate-950/80 backdrop-blur-xl">
            <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between gap-4 px-4 py-4 md:px-8">
                <div>
                    <p class="font-cinema text-2xl tracking-tight text-amber-300 md:text-3xl">MOVIESANALYZER</p>
                    <p class="text-xs uppercase tracking-[0.2em] text-muted">SMB Library Intelligence</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('cineclean.dashboard') }}" class="btn btn-ghost text-xs md:hidden">Home</a>
                    <a href="{{ route('cineclean.movies.index') }}" class="btn btn-ghost text-xs">Movies</a>
                    <a href="{{ route('cineclean.duplicates.index') }}" class="btn btn-ghost text-xs">Duplicates</a>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 pb-10 pt-6 md:px-8 md:pt-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-400/50 bg-emerald-800/40 px-4 py-3 text-sm text-emerald-50">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<div id="toast" class="fixed bottom-6 right-6 z-[100] hidden max-w-md rounded-xl border px-4 py-3 text-sm shadow-2xl"></div>

@if (app()->runningUnitTests())
    <script>
        window.MoviesAnalyzer = {
            csrf: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
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
            toast() {
            },
        };
    </script>
@endif

@livewireScripts
@stack('scripts')
</body>
</html>
