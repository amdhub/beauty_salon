@props(['number'])
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-lg bg-stone-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-muted']) }}>N° {{ $number }}</span>
