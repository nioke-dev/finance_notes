@props(['name', 'label' => null, 'value' => '', 'placeholder' => '0'])

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
            {{ $label }}
        </label>
    @endif
    <div class="relative rounded-xl shadow-sm">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-semibold">
            Rp
        </div>
        <input 
            type="number" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'block w-full pl-11 pr-4 py-2.5 bg-slate-800 border ' . ((isset($errors) && $errors->has($name)) ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-700 focus:ring-emerald-500 focus:border-emerald-500') . ' rounded-xl text-white placeholder-slate-500 text-sm font-medium focus:outline-none focus:ring-2 transition']) }}
        >
    </div>
    @if(isset($errors))
        @error($name)
            <p class="mt-1.5 text-xs text-rose-400 font-medium">{{ $message }}</p>
        @enderror
    @endif
</div>
