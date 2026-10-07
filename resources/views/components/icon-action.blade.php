@props(['href', 'label', 'icon' => 'eye'])
<a href="{{ $href }}" title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'icon-action']) }}><x-icon :name="$icon"/></a>
