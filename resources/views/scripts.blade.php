@php
    $usePublished = file_exists(public_path('vendor/catchy/catchy.js'));
    $js = '';
    if (!$usePublished) {
        $js = isset($jsPath) 
            ? \Hamzi\Catchy\Support\CatchyDirective::getJavaScript($jsPath)
            : 'console.warn("Catchy: resources/js/catchy.js not found.");';
    }
@endphp



<script>
    window.CatchyConfig = {!! json_encode([
        'containerId' => config('catchy.container_id', 'catchy-app'),
        'ignoreAttribute' => 'data-catchy-ignore',
        'prefetch' => config('catchy.prefetch.enabled', true),
        'prefetchDelay' => (int) config('catchy.prefetch.delay', 75),
        'cacheTTL' => (int) config('catchy.prefetch.ttl', 30000),
        'swr' => (bool) config('catchy.swr', true),
        'loadingBar' => config('catchy.loading_bar.enabled', true),
        'loadingBarHeight' => config('catchy.loading_bar.height', '3px'),
        'loadingBarColor' => config('catchy.loading_bar.color', 'linear-gradient(to right, #4f46e5, #06b6d4)'),
    ]) !!};
</script>

@if($usePublished)
    <script src="{{ asset('vendor/catchy/catchy.js') }}" defer></script>
@else
    <script>
        {!! $js !!}
    </script>
@endif
