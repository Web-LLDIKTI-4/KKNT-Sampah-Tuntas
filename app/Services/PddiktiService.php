<?php

namespace App\Services;

use App\Models\Satuanpendidikan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sinkronisasi data perguruan tinggi dari API PDDIKTI LLDIKTI IV.
 */
class PddiktiService
{
    // Hanya kolom ini yang disimpan dari respons API
    private const COLUMNS = [
        'id_sp', 'nm_lemb', 'npsn', 'nm_singkat', 'id_bp', 'jln', 'id_wil', 'kode_pos', 'no_tel', 'no_fax',
        'email', 'website', 'stat_sp', 'sk_pendirian_sp', 'tgl_sk_pendirian_sp', 'tgl_berdiri', 'id_stat_milik',
        'last_update', 'kota_kabupaten', 'provinsi',
    ];

    /** @return array<int, array>|null null bila koneksi gagal */
    public function allLldikti4(): ?array
    {
        return $this->post('/splldikti4/format/json', ['kodept' => '']);
    }

    public function findByKodept(string $kodept): ?array
    {
        return $this->post('/satuanpendidikanall/format/json', ['kodept' => $kodept, 'status' => 'A']);
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    public function upsert(array $row): string
    {
        $data = array_intersect_key($row, array_flip(self::COLUMNS));
        if (empty($data['id_sp']) || empty($data['npsn'])) {
            return 'skipped';
        }

        $sp = Satuanpendidikan::updateOrCreate(['id_sp' => $data['id_sp']], $data);

        return $sp->wasRecentlyCreated ? 'created' : 'updated';
    }

    private function post(string $path, array $payload): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(config('services.pddikti.timeout'))
                ->post(rtrim(config('services.pddikti.url'), '/').$path, $payload);
        } catch (Throwable $e) {
            Log::warning('PDDIKTI tidak dapat dihubungi', ['exception' => $e->getMessage()]);

            return null;
        }

        $json = $response->successful() ? $response->json() : null;

        return is_array($json) ? $json : null;
    }
}
