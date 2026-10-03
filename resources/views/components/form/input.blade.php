@props([
    'name' => null,
    'label',
    'type' => 'text',
    'value' => null,
    'inputClass' => 'form-control form-control-sm',
    'wrapperClass' => 'form-group form-floating form-floating-outline mb-6',
])
{{-- Input form-floating; slot = konten setelah label, <x-slot:label> untuk label ber-HTML --}}
<div class="{{ $wrapperClass }}">
    <input type="{{ $type }}" @if($name) name="{{ $name }}" @endif {{ $attributes->class($inputClass) }}
        @unless(in_array($type, ['password', 'file'], true)) value="{{ $name ? old($name, $value) : $value }}" @endunless>
    <label @if($attributes->has('id')) for="{{ $attributes->get('id') }}" @endif>{{ $label }}</label>
    {{ $slot }}
    @if($name)
        @error($name)<span class="errors-message text-danger d-block">{{ $message }}</span>@enderror
    @endif
</div>
