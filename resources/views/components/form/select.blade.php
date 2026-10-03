@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'placeholder' => '--pilih--',
    'inputClass' => 'form-control',
    'wrapperClass' => 'form-group form-floating form-floating-outline mb-6',
])
{{-- options: [value => teks]; opsi khusus lewat slot; :placeholder="false" = tanpa opsi kosong; <x-slot:after> = konten setelah label --}}
@php($current = (string) old($name, $selected))
<div class="{{ $wrapperClass }}">
    <select name="{{ $name }}" {{ $attributes->class($inputClass) }}>
        @if($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $optionValue => $optionText)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionText }}</option>
        @endforeach
        {{ $slot }}
    </select>
    <label @if($attributes->has('id')) for="{{ $attributes->get('id') }}" @endif>{{ $label }}</label>
    {{ $after ?? '' }}
    @error($name)<span class="errors-message text-danger d-block">{{ $message }}</span>@enderror
</div>
