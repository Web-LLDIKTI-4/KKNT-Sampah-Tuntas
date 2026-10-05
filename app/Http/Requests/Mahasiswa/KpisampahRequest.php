<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Kpisampah;
use App\Services\KpiSampahService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KpisampahRequest extends AjaxFormRequest
{
    public const FIELDS = [
        'bulan', 'jml_rw_kbs', 'jml_rw_non_kbs', 'jml_rumah', 'jml_rumah_memilah', 'timbulan',
        'pengurangan_organik', 'pengurangan_anorganik', 'residu', 'jml_bank_sampah',
    ];

    public const PESAN_DUPLIKAT = 'Anda sudah mengisi data sampah untuk bulan tersebut!';

    private ?string $idDesa = null;

    public function rules(): array
    {
        $bilangan = ['required', 'integer', 'min:0', 'max:1000000'];
        $berat = ['required', 'numeric', 'min:0', 'max:9999999999'];

        return [
            'id_sampah' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'bulan' => ['required', 'date_format:Y-m'],
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

            if ((float) $this->input('pengurangan_organik') + (float) $this->input('pengurangan_anorganik') > (float) $this->input('timbulan')) {
                $validator->errors()->add('pengurangan_anorganik', 'Total pengurangan tidak boleh melebihi jumlah timbulan sampah.');

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

    public function idDesa(): ?string
    {
        return $this->idDesa;
    }

    public function messages(): array
    {
        return [
            'bulan.required' => 'Bulan harus diisi.',
            'bulan.date_format' => 'Format bulan tidak valid.',
            'jml_rumah_memilah.lte' => 'Jumlah rumah yang memilah tidak boleh melebihi jumlah rumah keseluruhan.',
            '*.required' => 'Kolom ini harus diisi.',
            '*.min' => 'Nilai tidak boleh negatif.',
        ];
    }
}
