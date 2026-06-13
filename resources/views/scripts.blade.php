@php
    $js = (isset($jsPath) && file_exists($jsPath)) 
        ? file_get_contents($jsPath) 
        : 'console.warn("Catchy: resources/js/catchy.js not found.");';
@endphp

@if(config('catchy.include_morph', true))
    <script src="https://cdn.jsdelivr.net/npm/@alpinejs/morph@3.x.x/dist/cdn.min.js" defer></script>
@endif

<script>
    window.CatchyConfig = {!! json_encode([
        'containerId' => config('catchy.container_id', 'catchy-app'),
        'ignoreAttribute' => 'data-catchy-ignore',
        'prefetch' => config('catchy.prefetch.enabled', true),
        'prefetchDelay' => (int) config('catchy.prefetch.delay', 75),
        'cacheTTL' => (int) config('catchy.prefetch.ttl', 30000),
        'loadingBar' => config('catchy.loading_bar.enabled', true),
        'loadingBarHeight' => config('catchy.loading_bar.height', '3px'),
        'loadingBarColor' => config('catchy.loading_bar.color', 'linear-gradient(to right, #4f46e5, #06b6d4)'),
    ]) !!};
</script>

<script>
    {!! $js !!}
</script>
