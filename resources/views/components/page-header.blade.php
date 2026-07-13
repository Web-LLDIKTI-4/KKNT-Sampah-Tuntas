@props(['icon' => 'ri-information-2-fill', 'title' => null, 'subtitle' => null])
@php
    $resolvedTitle = $title;
    if (!$resolvedTitle) {
        $resolvedTitle = trim($slot);
    }
    if (!$resolvedTitle) {
        $resolvedTitle = $__env->yieldContent('title');
    }
    $resolvedSubtitle = $subtitle ?? ('Data ' . $resolvedTitle);
@endphp
<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
            <i class="{{ $icon }} ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
            <span class="align-middle">{{ $resolvedTitle }}</span>
        </h5>
        <span>{{ $resolvedSubtitle }}</span>
    </div>
</div>
