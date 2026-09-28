<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\SaranRequest;
use App\Models\Saran;

class SaranController extends Controller
{
    use RespondsWithJson;

    public function insert(SaranRequest $request)
    {
        Saran::create($request->validated());

        return $this->saved('Data berhasil dikirim');
    }
}
