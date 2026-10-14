@props([
    'name',
    'label' => null,
    'required' => false,
    'hint' => null,
])

<div class="space-y-1">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-slate-700">
            {{ $label }} {!! $required ? '<span class="text-rose-500">*</span>' : '' !!}
        </label>
    @endif

    <select name="{{ $name }}" 
            id="{{ $name }}" 
            {!! $required ? 'required' : '' !!}
            {{ $attributes->merge(['class' => 'w-full text-sm border rounded-lg px-3.5 py-2.5 outline-none transition bg-white ' . ($errors->has($name) ? 'border-rose-400 focus:ring-2 focus:ring-rose-200' : 'border-slate-300 focus:ring-2 focus:ring-[#8C5151] focus:border-transparent')]) }}>
        {{ $slot }}
    </select>

    @if($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-xs text-rose-500 font-medium">{{ $message }}</p>
    @enderror
</div>
