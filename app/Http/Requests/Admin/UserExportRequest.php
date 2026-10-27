<?php

namespace App\Http\Requests\Admin;

// Export of all users matching the table's filters, search and order
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
