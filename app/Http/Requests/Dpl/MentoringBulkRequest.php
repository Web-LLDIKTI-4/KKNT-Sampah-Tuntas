<?php

namespace App\Http\Requests\Dpl;

use App\Http\Requests\BulkEmailRequest;

class MentoringBulkRequest extends BulkEmailRequest
{
    protected int $maxItems = 500;

    // Hanya DPL yang menjadi pembimbing (menu dplmentoring hanya untuk DPL)
    public function authorize(): bool
    {
        return $this->user()?->role === 'dpl';
    }
}
