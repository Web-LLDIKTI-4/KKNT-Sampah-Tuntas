<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Dpl\MentoringInsertRequest;
use App\Models\Dplmentoring;

// Same createuser parsing/rules/messages as DPL, plus the target DPL
class MentoringAssignRequest extends MentoringInsertRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Dplmentoring::class) ?? false;
    }

    public function rules(): array
    {
        return ['id_dpl' => ['bail', 'required', 'string', 'uuid', 'exists:dpl,id_dpl']] + parent::rules();
    }

    public function messages(): array
    {
        return [
            'id_dpl.required' => 'DPL harus dipilih',
            'id_dpl.string' => 'DPL harus dipilih',
            'id_dpl.uuid' => 'DPL harus dipilih',
            'id_dpl.exists' => 'DPL harus dipilih',
        ] + parent::messages();
    }
}
