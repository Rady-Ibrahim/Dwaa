@props([
    'value' => null,
    'decimals' => 2,
])

@php
    $n = $value === null || $value === '' ? null : (float) $value;
@endphp

@if ($n === null)
    <span {{ $attributes->merge(['class' => 'text-zinc-600']) }}>—</span>
@else
    @php
        if ($n <= 0) {
            $tier = 'zero';
        } elseif ($n <= 8) {
            $tier = 'low';
        } elseif ($n <= 18) {
            $tier = 'mid';
        } elseif ($n <= 32) {
            $tier = 'high';
        } else {
            $tier = 'max';
        }

        $label = number_format($n, (int) $decimals, '.', ',');

        $shell = match ($tier) {
            'zero' => 'border border-zinc-700/70 bg-zinc-800/35 text-zinc-500 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]',
            'low' => 'border border-sky-500/35 bg-gradient-to-br from-sky-500/18 via-sky-600/8 to-transparent text-sky-100 shadow-[inset_0_1px_0_rgba(125,211,252,0.12)]',
            'mid' => 'border border-cyan-500/35 bg-gradient-to-br from-cyan-500/16 via-teal-600/10 to-transparent text-cyan-50 shadow-[inset_0_1px_0_rgba(34,211,238,0.12)]',
            'high' => 'border border-emerald-400/40 bg-gradient-to-br from-emerald-500/22 via-emerald-600/12 to-teal-900/20 text-emerald-50 shadow-[inset_0_1px_0_rgba(52,211,153,0.18)]',
            'max' => 'border border-amber-400/45 bg-gradient-to-br from-amber-500/28 via-orange-600/15 to-rose-900/10 text-amber-50 shadow-[inset_0_1px_0_rgba(251,191,36,0.2),0_0_20px_-8px_rgba(245,158,11,0.35)]',
        };
    @endphp
    <span
        {{ $attributes->merge([
            'class' => 'discount-pill inline-flex min-w-[2.75rem] items-center justify-center gap-0.5 rounded-xl px-2.5 py-1 text-sm font-semibold tabular-nums tracking-tight '.$shell,
            'data-discount-tier' => $tier,
        ]) }}
        dir="ltr"
    >
        <span>{{ $label }}</span>
        <span class="text-[0.65rem] font-medium opacity-75">%</span>
    </span>
@endif
