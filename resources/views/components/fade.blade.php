@props([
    'duration' => '300',
    'class' => ''
])

<div 
    x-data="{ show: false }" 
    x-init="$nextTick(() => show = true)"
    x-show="show"
    x-transition:enter="transition ease-out duration-{{ $duration }}"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="{{ $class }}"
>
    {{ $slot }}
</div>
