@props(['formId', 'class' => 'btn btn-sm btn-primary', 'name' => null])
<button type="submit" id="btnSubmit_{{ $formId }}" class="{{ $class }}"@if($name) name="{{ $name }}"@endif>{{ $slot }}</button>
