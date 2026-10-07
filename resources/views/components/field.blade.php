@props(['name','label','value'=>null,'type'=>'text','required'=>false])
<div class="field"><label for="{{ $name }}">{{ $label }} @if($required)<span class="text-wine" aria-hidden="true">*</span>@endif</label>
@if($type==='textarea')<textarea id="{{ $name }}" name="{{ $name }}" rows="3" @required($required) {{ $attributes }}>{{ old($name,$value) }}</textarea>
@else<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if($type!=='file' && $type!=='password') value="{{ old($name,$value) }}" @endif @required($required) {{ $attributes }} />@endif
@error($name)<p class="text-xs text-rose-700">{{ $message }}</p>@enderror</div>
