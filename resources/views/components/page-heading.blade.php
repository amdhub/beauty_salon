@props(['title','description'=>null])
<div class="mb-7 flex flex-wrap items-center justify-between gap-4"><div><h1 class="serif text-3xl md:text-4xl">{{ $title }}</h1>@if($description)<p class="mt-2 text-sm leading-6 text-muted">{{ $description }}</p>@endif</div><div class="flex flex-wrap items-center gap-3">{{ $slot }}</div></div>
