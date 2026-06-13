@props([
    'action' => '',
    'method' => 'POST',
    'beforesend' => null,
    'success' => null,
    'error' => null,
])

@php
    $method = strtoupper($method);
    $formMethod = $method === 'GET' ? 'GET' : 'POST';
    $spoofMethod = !in_array($method, ['GET', 'POST']) ? $method : null;
@endphp

<form 
    action="{{ $action }}" 
    method="{{ $formMethod }}"
    x-data
    @if($beforesend) @catchy:start="{{ $beforesend }}" @endif
    @if($success) @catchy:end="{{ $success }}" @endif
    @if($error) @catchy:error="{{ $error }}" @endif
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
