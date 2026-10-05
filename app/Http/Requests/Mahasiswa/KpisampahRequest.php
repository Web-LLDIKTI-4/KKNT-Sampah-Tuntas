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
        'jml_rw_kbs', 'jml_rw_non_kbs', 'jml_rumah', 'jml_rumah_memilah', 'timbulan',
        'pengurangan_organik', 'pengurangan_anorganik', 'residu', 'jml_bank_sampah',
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
        $berat = ['required', 'numeric', 'min:0', 'max:9999999999'];

        return [
            'jml_rw_kbs' => $bilangan,
            'jml_rw_non_kbs' => $bilangan,
            'jml_rumah' => $bilangan,
            'jml_rumah_memilah' => [...$bilangan, 'lte:jml_rumah'],
            'timbulan' => $berat,
            'pengurangan_organik' => $berat,
            'pengurangan_anorganik' => $berat,
            'residu' => $berat,
            'jml_bank_sampah' => $bilangan,
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

    // Total pengurangan ≤ timbulan; false bila gagal (error sudah ditambahkan)
    public static function cekPengurangan(Validator $validator, array $input): bool
    {
        if ((float) ($input['pengurangan_organik'] ?? 0) + (float) ($input['pengurangan_anorganik'] ?? 0) > (float) ($input['timbulan'] ?? 0)) {
            $validator->errors()->add('pengurangan_anorganik', 'Total pengurangan tidak boleh melebihi jumlah timbulan sampah.');

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
        ];
    }
}
