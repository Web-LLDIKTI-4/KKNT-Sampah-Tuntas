<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dpl\FreeformRequest;
use App\Models\Freeform;

class DplfreeformController extends NilaiMahasiswaController
{
    protected function model(): string
    {
        return Freeform::class;
    }

    protected function viewPrefix(): string
    {
        return 'freeform.dpl';
    }

    protected function routePrefix(): string
    {
        return 'dplfreeform';
    }

    protected function fields(): array
    {
        return ['freeform'];
    }

    protected function formData(): array
    {
        return ['freeform' => Freeform::KOMPONEN];
    }

    public function insert(FreeformRequest $request)
    {
        return $this->store($request);
    }

    public function update(FreeformRequest $request)
    {
        return $this->change($request);
    }
}
