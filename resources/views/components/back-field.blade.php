@php
    $previous = url()->previous();
    $valid = \Illuminate\Support\Str::startsWith($previous, url('/'))
        && rtrim($previous, '/') !== rtrim(url()->current(), '/');
@endphp
@if($valid)
    <input type="hidden" name="_back" value="{{ $previous }}">
@endif
