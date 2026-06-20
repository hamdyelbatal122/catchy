@props([
    'action' => '',
    'method' => 'POST',
    'beforesend' => null,
    'success' => null,
    'error' => null,
    'onbeforesend' => null,
    'onsuccess' => null,
    'onerror' => null,
])

@php
    $method = strtoupper($method);
    $formMethod = $method === 'GET' ? 'GET' : 'POST';
    $spoofMethod = !in_array($method, ['GET', 'POST']) ? $method : null;

    $directiveOptions = array_filter([
        'beforesend' => $beforesend ?? $onbeforesend,
        'success' => $success ?? $onsuccess,
        'error' => $error ?? $onerror,
    ]);
@endphp

<form 
    action="{{ $action }}" 
    method="{{ $formMethod }}"
    {!! \Hamzi\Catchy\Support\CatchyDirective::render($directiveOptions) !!}
    {{ $attributes }}
>
    @if($formMethod === 'POST')
        @csrf
    @endif

    @if($spoofMethod)
        @method($spoofMethod)
    @endif

    {{ $slot }}
</form>
