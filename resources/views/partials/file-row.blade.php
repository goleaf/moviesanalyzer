@php
    $extensionColor = [
        'mkv' => 'bg-blue-600/20 text-blue-300 border-blue-500/30',
        'mp4' => 'bg-green-600/20 text-green-300 border-green-500/30',
        'avi' => 'bg-orange-600/20 text-orange-200 border-orange-500/30',
        'mov' => 'bg-sky-600/20 text-sky-300 border-sky-500/30',
        'wmv' => 'bg-pink-600/20 text-pink-300 border-pink-500/30',
    ][$file->extension] ?? 'bg-slate-600/20 text-slate-200 border-slate-500/30';
@endphp

<tr class="border-t border-cine" data-file-row="{{ $file->id }}">
    <td class="px-4 py-3 font-mono text-xs md:text-sm break-all">{{ $file->filename }}</td>
    <td class="px-4 py-3 text-xs text-muted break-all">{{ $file->smb_path }}</td>
    <td class="px-4 py-3 text-xs">{{ $file->formatted_size }}</td>
    <td class="px-4 py-3">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] uppercase tracking-wide {{ $extensionColor }}">
            {{ $file->extension }}
        </span>
    </td>
    <td class="px-4 py-3 text-right">
        <button
            type="button"
            class="delete-trigger inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs border border-red-500/40 bg-red-900/30 hover:bg-red-800/40 transition"
            data-id="{{ $file->id }}"
            data-filename="{{ $file->filename }}"
            data-size="{{ $file->formatted_size }}"
            data-path="{{ $file->smb_path }}"
            data-url="{{ route('cineclean.file.destroy', $file) }}"
        >
            DELETE
        </button>
    </td>
</tr>
