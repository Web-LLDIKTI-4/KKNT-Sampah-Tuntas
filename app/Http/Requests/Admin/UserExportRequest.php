<?php

namespace App\Http\Requests\Admin;

// Export of the user table's current view: filters + search, order, page
class UserExportRequest extends UserFilterRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...TableExportRequest::pageRules(),
        ];
    }
}
