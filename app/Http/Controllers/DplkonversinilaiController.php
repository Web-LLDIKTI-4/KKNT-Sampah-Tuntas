<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dpl\KonversiNilaiRequest;
use App\Models\Nilaikonversi;

class DplkonversinilaiController extends NilaiMahasiswaController
{
    protected function model(): string
    {
        return Nilaikonversi::class;
    }

    protected function viewPrefix(): string
    {
        return 'konversinilai';
    }

    protected function routePrefix(): string
    {
        return 'dplkonversinilai';
    }

    protected function fields(): array
    {
        return ['matakuliah', 'sks'];
    }

    public function insert(KonversiNilaiRequest $request)
    {
        return $this->store($request);
    }

    public function update(KonversiNilaiRequest $request)
    {
        return $this->change($request);
    }
}
