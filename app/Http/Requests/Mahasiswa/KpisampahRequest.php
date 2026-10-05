<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Kpisampah;
use App\Services\KpiSampahService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KpisampahRequest extends AjaxFormRequest
{
    public const SAMPAH_FIELDS = [
        'jml_rw', 'jml_penduduk', 'jml_rumah', 'jml_rumah_memilah', 'timbulan',
        'organik_sumber', 'organik_metode', 'organik_metode_unit',
        'organik_dlh', 'organik_dlh_fasilitas', 'organik_dlh_lokasi',
        'anorganik_sumber', 'anorganik_metode', 'anorganik_metode_lokasi', 'anorganik_metode_unit',
        'keterangan',
    ];

    public const FIELDS = ['bulan', ...self::SAMPAH_FIELDS];

    public const PESAN_DUPLIKAT = 'Anda sudah mengisi data sampah untuk bulan tersebut!';

    private ?string $idDesa = null;

    public function rules(): array
    {
        return [
            'id_sampah' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'bulan' => ['required', 'date_format:Y-m'],
            ...self::sampahRules(),
        ];
    }

    // Rules angka sampah (tanpa bulan), dipakai ulang KpicapaianRequest
    public static function sampahRules(): array
    {
        $bilangan = ['required', 'integer', 'min:0', 'max:1000000'];
        $berat = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999'];
        // Awalan = + - @ ditolak agar tidak jadi formula saat diekspor ke Excel
        $teks = ['nullable', 'string', 'max:255', 'not_regex:/^[=+\-@]/'];

        return [
            'jml_rw' => $bilangan,
            'jml_penduduk' => $bilangan,
            'jml_rumah' => $bilangan,
            'jml_rumah_memilah' => [...$bilangan, 'lte:jml_rumah'],
            'timbulan' => $berat,
            'organik_sumber' => $berat,
            'organik_metode' => $teks,
            'organik_metode_unit' => $bilangan,
            'organik_dlh' => $berat,
            'organik_dlh_fasilitas' => $teks,
            'organik_dlh_lokasi' => $teks,
            'anorganik_sumber' => $berat,
            'anorganik_metode' => $teks,
            'anorganik_metode_lokasi' => $teks,
            'anorganik_metode_unit' => $bilangan,
            'keterangan' => ['nullable', 'string', 'max:2000', 'not_regex:/^[=+\-@]/'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('bulan') > now()->format('Y-m') || $this->input('bulan') < '2020-01') {
                $validator->errors()->add('bulan', 'Bulan tidak boleh melebihi bulan berjalan.');

                return;
            }

            if (! self::cekPengurangan($validator, $this->all())) {
                return;
            }

            // Saat edit kelurahan tetap mengikuti data tersimpan
            $this->idDesa = $this->isUpdate()
                ? Kpisampah::ownedBy($this->user())->whereKey($this->input('id_sampah'))->value('id_desa')
                : app(KpiSampahService::class)->desaKetua($this->user()->email);

            if (! $this->idDesa) {
                $validator->errors()->add('bulan', 'Anda belum terdaftar sebagai ketua kelompok atau belum memilih kelurahan.');

                return;
            }

            // 1 data per ketua per bulan
            $duplikat = Kpisampah::where('email', $this->user()->email)
                ->where('bulan', $this->input('bulan').'-01')
                ->when($this->input('id_sampah'), fn ($q, $id) => $q->whereKeyNot($id))
                ->exists();
            if ($duplikat) {
                $validator->errors()->add('bulan', self::PESAN_DUPLIKAT);
            }
        }];
    }

    // Total pengolahan (J+M+P) ≤ timbulan; false bila gagal (error sudah ditambahkan)
    public static function cekPengurangan(Validator $validator, array $input): bool
    {
        $total = (float) ($input['organik_sumber'] ?? 0) + (float) ($input['organik_dlh'] ?? 0) + (float) ($input['anorganik_sumber'] ?? 0);
        if (round($total, 2) > (float) ($input['timbulan'] ?? 0)) {
            $validator->errors()->add('anorganik_sumber', 'Total pengolahan tidak boleh melebihi jumlah timbulan sampah.');

            return false;
        }

        return true;
    }

    public function idDesa(): ?string
    {
        return $this->idDesa;
    }

    public function messages(): array
    {
        return [
            'bulan.required' => 'Bulan harus diisi.',
            'bulan.date_format' => 'Format bulan tidak valid.',
            ...self::sampahMessages(),
        ];
    }

    public static function sampahMessages(): array
    {
        return [
            'jml_rumah_memilah.lte' => 'Jumlah rumah yang memilah tidak boleh melebihi jumlah rumah keseluruhan.',
            '*.required' => 'Kolom ini harus diisi.',
            '*.min' => 'Nilai tidak boleh negatif.',
            '*.max' => 'Nilai melebihi batas maksimum.',
            '*.decimal' => 'Maksimal 2 angka di belakang koma.',
            '*.not_regex' => 'Teks tidak boleh diawali tanda = + - @.',
        ];
    }
}
