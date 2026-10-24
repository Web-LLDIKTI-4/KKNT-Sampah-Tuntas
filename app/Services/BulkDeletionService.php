<?php

namespace App\Services;

use App\Support\BulkResult;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hapus massal: tiap data dihapus lewat $delete (satu transaksi per data),
 * satu kegagalan tidak membatalkan data lain.
 */
class BulkDeletionService
{
    /**
     * @param  Collection<int, Model>  $records
     * @param  Closure(Model): void  $delete
     * @param  Closure(Model): string  $label
     * @param  (Closure(Model): ?string)|null  $skipReason
     */
    public function run(Collection $records, array $requestedIds, string $keyName, Closure $delete, Closure $label, ?Closure $skipReason = null): BulkResult
    {
        $result = new BulkResult;
        $records = $records->keyBy($keyName);
        // Emails already removed by an earlier cascade (same email on several rows)
        $deletedEmails = [];

        foreach ($requestedIds as $id) {
            $record = $records->get($id);
            if (! $record) {
                $result->skip($id, $id, 'Data tidak ditemukan / sudah terhapus');

                continue;
            }
            if ($skipReason && ($reason = $skipReason($record))) {
                $result->skip($id, $label($record), $reason);

                continue;
            }
            $email = $record->email ? mb_strtolower($record->email) : null;
            if ($email !== null && isset($deletedEmails[$email])) {
                $result->processed();

                continue;
            }

            try {
                $delete($record);
                $result->processed();
                if ($email !== null) {
                    $deletedEmails[$email] = true;
                }
            } catch (Throwable $e) {
                Log::error('Hapus massal gagal', ['model' => class_basename($record), 'id' => $id, 'exception' => $e]);
                $result->fail($id, $label($record));
            }
        }

        return $result;
    }
}
