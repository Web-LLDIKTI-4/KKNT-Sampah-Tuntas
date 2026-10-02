@props(['formId', 'variant' => 'primary', 'size' => 'sm', 'icon' => 'ri-save-fill'])
{{-- Submit form; id btnSubmit_{formId} dipakai public/js/crud.js untuk state loading --}}
<x-button type="submit" id="btnSubmit_{{ $formId }}" :variant="$variant" :size="$size" :icon="$icon" {{ $attributes }}>{{ $slot }}</x-button>
