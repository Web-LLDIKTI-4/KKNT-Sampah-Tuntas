<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithJson
{
    protected function saved(string $message = 'Data berhasil disimpan'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message]);
    }

    protected function failed(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    protected function notFound(): JsonResponse
    {
        return $this->failed('Data tidak ditemukan!', 404);
    }

    protected function deleted(string $message = 'Data berhasil dihapus'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message]);
    }

    // Frontend hapus membaca key "error" untuk penolakan
    protected function deleteRejected(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'error' => $message], $status);
    }
}
