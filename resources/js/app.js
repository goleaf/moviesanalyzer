import './bootstrap';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const formatBytes = (bytes) => {
    if (!Number.isFinite(bytes) || bytes < 1024) {
        return `${Math.max(0, Number(bytes) || 0)} B`;
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
};

const toast = (message, type = 'success') => {
    const el = document.getElementById('toast');

    if (!el) {
        return;
    }

    const colors = {
        success: 'border-emerald-400/60 bg-emerald-700/90 text-emerald-50',
        error: 'border-rose-400/70 bg-rose-700/90 text-rose-50',
        info: 'border-slate-500/70 bg-slate-800/90 text-slate-50',
    };

    el.className = `fixed bottom-6 right-6 z-[100] max-w-md rounded-xl border px-4 py-3 text-sm shadow-2xl transition ${colors[type] ?? colors.info}`;
    el.textContent = String(message ?? '');
    el.classList.remove('hidden');

    window.setTimeout(() => {
        el.classList.add('hidden');
    }, 3400);
};

window.MoviesAnalyzer = {
    csrf: csrfToken,
    formatBytes,
    toast,
};

window.addEventListener('notify', (event) => {
    const detail = event?.detail ?? {};
    const message = detail.message ?? detail[0] ?? 'Operation complete.';
    const type = detail.type ?? detail[1] ?? 'info';

    toast(message, type);
});
