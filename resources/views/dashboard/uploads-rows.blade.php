@forelse ($uploads as $up)
    <tr class="hover:bg-zinc-900/40" data-upload-status="{{ $up->status }}">
        <td class="px-4 py-3 text-zinc-500">{{ $up->id }}</td>
        <td class="px-4 py-3 text-white">{{ $up->supplier->name ?? '—' }}</td>
        <td class="px-4 py-3" @if ($up->error_msg) title="{{ $up->error_msg }}" @endif>
            <span class="rounded-full px-2 py-0.5 text-xs {{
                match ($up->status) {
                    'pending' => 'bg-zinc-800 text-zinc-400',
                    'processing' => 'bg-amber-500/15 text-amber-400',
                    'done' => 'bg-emerald-500/15 text-emerald-400',
                    'failed' => 'bg-rose-500/15 text-rose-400',
                    default => 'bg-zinc-800 text-zinc-400',
                }
            }}">{{ $up->status }}</span>
        </td>
        <td class="px-4 py-3 text-zinc-400">{{ $up->total_rows }}</td>
        <td class="px-4 py-3 text-emerald-400">{{ $up->matched_count }}</td>
        <td class="px-4 py-3 text-amber-400">{{ $up->unmatched_count }}</td>
        <td class="px-4 py-3 text-xs text-zinc-500">{{ $up->created_at?->format('Y-m-d H:i') }}</td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-4 py-8 text-center text-zinc-500">لا توجد رفوعات بعد.</td>
    </tr>
@endforelse
