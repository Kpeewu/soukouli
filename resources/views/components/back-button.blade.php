@props(['fallback', 'label' => 'Retour', 'class' => 'btn btn-secondary'])
@php
    $previous = url()->previous();
    $sameOrigin = \Illuminate\Support\Str::startsWith($previous, url('/'));
    $target = ($sameOrigin && rtrim($previous, '/') !== rtrim(url()->current(), '/')) ? $previous : $fallback;
@endphp
<a href="{{ $target }}" {{ $attributes->merge(['class' => $class]) }}>
    <i class="fa fa-angle-left mr-1" aria-hidden="true"></i>{{ $label }}
</a>
