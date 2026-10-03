@props([
    'name',
    'label',
    'value' => null,
    'inputClass' => 'form-control',
    'wrapperClass' => 'form-group form-floating form-floating-outline mb-6',
])
<div class="{{ $wrapperClass }}">
    <textarea name="{{ $name }}" {{ $attributes->class($inputClass) }}>{{ old($name, $value) }}</textarea>
    <label @if($attributes->has('id')) for="{{ $attributes->get('id') }}" @endif>{{ $label }}</label>
    {{ $slot }}
    @error($name)<span class="errors-message text-danger d-block">{{ $message }}</span>@enderror
</div>
