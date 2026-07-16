@props(['formId', 'class' => 'btn btn-sm btn-primary', 'name' => null, 'icon' => 'ri-save-fill'])
<button type="submit" id="btnSubmit_{{ $formId }}" class="{{ $class }}"@if($name) name="{{ $name }}"@endif>
    <i class="{{ $icon }} me-2"></i>
    {{ $slot }}
</button>
