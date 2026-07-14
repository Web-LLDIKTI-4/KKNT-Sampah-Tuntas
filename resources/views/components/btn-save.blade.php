@props(['formId', 'class' => 'btn btn-sm btn-primary', 'name' => null])
<button type="submit" id="btnSubmit_{{ $formId }}" class="{{ $class }}"@if($name) name="{{ $name }}"@endif>
    <i class="ri-save-fill me-2"></i>
    {{ $slot }}
</button>
