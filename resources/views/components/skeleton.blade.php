@props([
    'type' => 'text',
    'lines' => 1,
    'animate' => 'pulse',
])

@php
    $animation = $animate === 'pulse' ? 'animate-pulse' : '';
@endphp

<div {{ $attributes->merge(['class' => "{$animation} space-y-3"]) }}>
    @if ($type === 'circle')
        <div class="rounded-full bg-gray-200 dark:bg-slate-700 h-12 w-12"></div>
    @elseif ($type === 'title')
        <div class="h-6 bg-gray-200 dark:bg-slate-700 rounded-md w-2/3"></div>
    @elseif ($type === 'card')
        <div class="rounded-lg bg-gray-200 dark:bg-slate-700 h-32 w-full"></div>
    @else
        @for ($i = 0; $i < $lines; $i++)
            <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded-md {{ $i === $lines - 1 && $lines > 1 ? 'w-5/6' : 'w-full' }}"></div>
        @endfor
    @endif
</div>
