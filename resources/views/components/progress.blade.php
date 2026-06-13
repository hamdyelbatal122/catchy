@props([
    'color' => 'primary', // primary, accent, success, warning, danger, gradient
    'height' => 'h-2.5', // Tailwind height class
    'showPercent' => true,
    'label' => 'جاري تحميل الملفات...',
])

@php
    $colors = [
        'primary' => 'bg-indigo-600 dark:bg-indigo-500',
        'accent' => 'bg-cyan-500 dark:bg-cyan-400',
        'success' => 'bg-emerald-500 dark:bg-emerald-400',
        'warning' => 'bg-amber-500 dark:bg-amber-400',
        'danger' => 'bg-rose-500 dark:bg-rose-400',
        'gradient' => 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500',
    ];
    $colorClass = $colors[$color] ?? $colors['primary'];
@endphp

<div 
    x-data="{ 
        show: false, 
        progress: 0,
        init() {
            const form = this.$el.closest('form');
            const target = form || window;
            
            const handleStart = () => {
                this.show = true;
                this.progress = 0;
            };
            
            const handleProgress = (e) => {
                this.show = true;
                this.progress = e.detail.percent;
            };
            
            const handleEnd = () => {
                this.progress = 100;
                setTimeout(() => {
                    this.show = false;
                    setTimeout(() => { this.progress = 0; }, 300);
                }, 500);
            };
            
            const handleError = () => {
                setTimeout(() => {
                    this.show = false;
                    setTimeout(() => { this.progress = 0; }, 300);
                }, 500);
            };
            
            target.addEventListener('catchy-start', handleStart);
            target.addEventListener('catchy:start', handleStart);
            target.addEventListener('catchy-progress', handleProgress);
            target.addEventListener('catchy:progress', handleProgress);
            target.addEventListener('catchy-end', handleEnd);
            target.addEventListener('catchy:end', handleEnd);
            target.addEventListener('catchy-error', handleError);
            target.addEventListener('catchy:error', handleError);
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    {{ $attributes->merge([
        'class' => 'w-full space-y-2',
        'style' => 'display: none;'
    ]) }}
>
    @if ($showPercent)
        <div class="flex justify-between items-center text-xs font-semibold text-gray-700 dark:text-gray-300">
            <span x-text="progress === 100 ? 'اكتمل التحميل' : '{{ $label }}'">{{ $label }}</span>
            <span x-text="progress + '%'">0%</span>
        </div>
    @endif
    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-300/30 dark:border-gray-750/30">
        <div 
            class="{{ $height }} {{ $colorClass }} rounded-full transition-all duration-300 ease-out shadow-sm"
            :style="{ width: progress + '%' }"
        ></div>
    </div>
</div>
