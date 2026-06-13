@props([
    'position' => 'top-right', // top-right, top-left, bottom-right, bottom-left
    'duration' => 4000,
])

@php
    $positions = [
        'top-right' => 'top-5 right-5',
        'top-left' => 'top-5 left-5',
        'bottom-right' => 'bottom-5 right-5',
        'bottom-left' => 'bottom-5 left-5',
    ];
    $positionClass = $positions[$position] ?? $positions['top-right'];
@endphp

<div
    {{ $attributes->merge([
        'class' => "fixed {$positionClass} z-50 flex flex-col gap-3 w-full max-w-sm pointer-events-none"
    ]) }}
    x-data="{
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type, timer: null });
            
            this.$nextTick(() => {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.timer = setTimeout(() => this.remove(id), {{ $duration }});
                }
            });
        },
        remove(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                clearTimeout(this.toasts[index].timer);
                this.toasts.splice(index, 1);
            }
        }
    }"
    @catchy:flash.window="
        Object.entries($event.detail).forEach(([type, msg]) => add(msg, type))
    "
    @catchy-flash.window="
        Object.entries($event.detail).forEach(([type, msg]) => add(msg, type))
    "
    x-init="
        @if(session()->has('success')) add(@js(session('success')), 'success'); @endif
        @if(session()->has('error')) add(@js(session('error')), 'error'); @endif
        @if(session()->has('warning')) add(@js(session('warning')), 'warning'); @endif
        @if(session()->has('info')) add(@js(session('info')), 'info'); @endif
        @if(session()->has('status')) add(@js(session('status')), 'info'); @endif
    "
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-xl border bg-white dark:bg-slate-900 shadow-xl transition-all duration-300"
            :class="{
                'border-emerald-100 dark:border-emerald-900/40': toast.type === 'success',
                'border-rose-100 dark:border-rose-900/40': toast.type === 'error',
                'border-amber-100 dark:border-amber-900/40': toast.type === 'warning',
                'border-blue-100 dark:border-blue-900/40': toast.type === 'info' || toast.type === 'status'
            }"
        >
            <div class="p-4 flex items-start gap-3">
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <!-- Success Icon -->
                    <template x-if="toast.type === 'success'">
                        <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-950/50 p-1.5 text-emerald-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                    </template>
                    <!-- Error Icon -->
                    <template x-if="toast.type === 'error'">
                        <span class="inline-flex rounded-full bg-rose-50 dark:bg-rose-950/50 p-1.5 text-rose-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </span>
                    </template>
                    <!-- Warning Icon -->
                    <template x-if="toast.type === 'warning'">
                        <span class="inline-flex rounded-full bg-amber-50 dark:bg-amber-950/50 p-1.5 text-amber-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </span>
                    </template>
                    <!-- Info Icon -->
                    <template x-if="toast.type === 'info' || toast.type === 'status'">
                        <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-950/50 p-1.5 text-blue-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.083.985l-.04.025m-.006-1.043l-.015 2.87-.04.025m1.235-2.883a.75.75 0 00-1.08 0l-1.2 1.2a.75.75 0 101.06 1.06l.825-.824m0 .004v.003h.008v-.008h-.008v.005zm0-.005L12 15.75m0-11.25a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </template>
                </div>

                <!-- Content -->
                <div class="flex-1 pt-0.5">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="toast.message"></p>
                </div>

                <!-- Close Button -->
                <div class="flex-shrink-0 flex">
                    <button
                        type="button"
                        @click="remove(toast.id)"
                        class="inline-flex rounded-lg p-1 text-slate-400 hover:text-slate-500 focus:outline-none"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="h-1 bg-slate-100 dark:bg-slate-800">
                <div 
                    class="h-full transition-all linear" 
                    :class="{
                        'bg-emerald-500': toast.type === 'success',
                        'bg-rose-500': toast.type === 'error',
                        'bg-amber-500': toast.type === 'warning',
                        'bg-blue-500': toast.type === 'info' || toast.type === 'status'
                    }"
                    :style="`width: 100%; transition: width ${ {{ $duration }} }ms linear;`"
                    x-init="setTimeout(() => $el.style.width = '0%', 50)"
                ></div>
            </div>
        </div>
    </template>
</div>
