<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

// Outcome of a bulk action: processed count plus skipped/failed items for the result toast
class BulkResult
{
    private int $processed = 0;

    private array $skipped = [];

    private array $failed = [];

    public function processed(int $count = 1): static
    {
        $this->processed += $count;

        return $this;
    }

    public function skip(string $id, string $label, string $reason): static
    {
        $this->skipped[] = ['id' => $id, 'label' => $label, 'reason' => $reason];

        return $this;
    }

    public function fail(string $id, string $label): static
    {
        $this->failed[] = ['id' => $id, 'label' => $label];

        return $this;
    }

    public function processedCount(): int
    {
        return $this->processed;
    }

    public function toResponse(): JsonResponse
    {
        $skipped = count($this->skipped);
        $failed = count($this->failed);

        $message = "{$this->processed} berhasil diproses";
        if ($skipped > 0) {
            $message .= ", {$skipped} dilewati";
        }
        if ($failed > 0) {
            $message .= ", {$failed} gagal";
        }

        return response()->json([
            'success' => true,
            'toast' => match (true) {
                $skipped === 0 && $failed === 0 => 'success',
                $this->processed > 0 => 'warning',
                default => 'error',
            },
            'message' => $message.'.',
            'processed' => $this->processed,
            'skipped' => $skipped,
            'failed' => $failed,
            'skipped_items' => $this->skipped,
            'failed_items' => $this->failed,
        ]);
    }
}
