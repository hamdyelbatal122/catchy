@props([
    'src',
    'trigger' => 'load', // load, intersect
    'placeholder' => null,
])

<div 
    x-data="{ 
        loaded: false,
        error: false,
        load() {
            if (this.loaded) return;
            
            // Trigger start event
            window.dispatchEvent(new CustomEvent('catchy:start'));
            window.dispatchEvent(new CustomEvent('catchy-start'));
            
            fetch('{{ $src }}', {
                headers: {
                    'X-Catchy-SPA': 'true'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Lazy load failed');
                }
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const containerId = '{{ config('catchy.container_id', 'catchy-app') }}';
                const fragment = doc.getElementById(containerId) || doc.body;
                
                // Set the inner HTML of the lazy container to the loaded container
                this.$el.innerHTML = fragment.innerHTML;
                this.loaded = true;
                
                // Re-execute scripts within the newly added content
                const scripts = this.$el.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    if (oldScript.hasAttribute('data-catchy-ignore')) return;
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                
                // Dispatch end event
                window.dispatchEvent(new CustomEvent('catchy:end'));
                window.dispatchEvent(new CustomEvent('catchy-end'));
            })
            .catch(err => {
                console.error(err);
                this.error = true;
                window.dispatchEvent(new CustomEvent('catchy:error', { detail: { error: err } }));
                window.dispatchEvent(new CustomEvent('catchy-error', { detail: { error: err } }));
            });
        },
        init() {
            if ('{{ $trigger }}' === 'intersect') {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.load();
                            observer.disconnect();
                        }
                    });
                }, { threshold: 0.1 });
                observer.observe(this.$el);
            } else {
                this.load();
            }
        }
    }"
    {{ $attributes }}
>
    <template x-if="!loaded && !error">
        <div>
            @if ($placeholder)
                {!! $placeholder !!}
            @else
                <div class="space-y-3">
                    <x-catchy-skeleton type="title" />
                    <x-catchy-skeleton type="text" lines="3" />
                </div>
            @endif
        </div>
    </template>
    <template x-if="error">
        <div class="text-sm text-rose-600 dark:text-rose-400 p-4 border border-rose-200 dark:border-rose-900/40 rounded-lg bg-rose-50 dark:bg-rose-950/20">
            {{ __('catchy::messages.loading_lazy') }} - Connection Error
        </div>
    </template>
</div>
