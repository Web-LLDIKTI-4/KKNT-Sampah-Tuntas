<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Models\Pjdesa;
use App\Services\KpiSampahService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KpicapaianRequest extends AjaxFormRequest
{
    public const DUPLIKAT_BULAN = 'Capaian untuk bulan tersebut sudah dibuat, silakan edit.';

    private ?string $idDesa = null;

    // Tanggal input dinormalisasi ke awal bulan (Y-m-d)
    public function bulan(): string
    {
        return Carbon::parse($this->validated('bulan'))->startOfMonth()->toDateString();
    }

    public function rules(): array
    {
        return [
            'id_capaian' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'id_kpi' => ['required', 'uuid', 'exists:kpi,id_kpi'],
            'bulan' => ['required', 'date', 'after_or_equal:2020-01-01', 'before_or_equal:today'],
            'status_capaian' => ['required', Rule::in(array_keys(Kpicapaian::STATUS))],
            'tautan' => ['required', 'url:http,https', 'max:2000'],
            'permasalahan' => ['required', 'string', 'max:5000'],
            'solusi' => ['required', 'string', 'max:5000'],
            'kendala' => ['required', 'string', 'max:5000'],
            ...KpisampahRequest::sampahRules(),
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! KpisampahRequest::cekPengurangan($validator, $this->all())) {
                return;
            }

            $email = $this->user()->email;

            if (! Pjdesa::where('email', $email)->exists()) {
                $validator->errors()->add('kendala', 'Anda tidak memiliki akses untuk menyimpan data capaian KPI. Silakan hubungi administrator.');

                return;
            }

            // Satu capaian per (email, bulan); saat update kecualikan data sendiri
            $duplikat = Kpicapaian::where('email', $email)->where('bulan', $this->bulan())
                ->when($this->isUpdate(), fn ($q) => $q->whereKeyNot($this->input('id_capaian')))
                ->exists();
            if ($duplikat) {
                $validator->errors()->add('bulan', self::DUPLIKAT_BULAN);

                return;
            }

            // Desa ketua hanya wajib bila belum ada baris sampah (bulan lama/baru) yang bisa dipakai
            $bulanLama = $this->isUpdate() ? Kpicapaian::whereKey($this->input('id_capaian'))->value('bulan') : null;
            $adaBaris = Kpisampah::where('email', $email)->whereIn('bulan', array_filter([$bulanLama, $this->bulan()]))->exists();
            $this->idDesa = app(KpiSampahService::class)->desaKetua($email);
            if (! $this->idDesa && ! $adaBaris) {
                $validator->errors()->add('bulan', 'Anda belum terdaftar sebagai ketua kelompok atau belum memilih kelurahan.');
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
            'id_kpi.required' => 'KPI harus dipilih.',
            'bulan.required' => 'Tanggal harus diisi.',
            'bulan.date' => 'Tanggal tidak valid.',
            'bulan.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
            'bulan.after_or_equal' => 'Tanggal minimal Januari 2020.',
            'tautan.required' => 'Tautan harus isi.',
            'tautan.url' => 'Tautan harus berupa URL http/https yang valid.',
            'permasalahan.required' => 'Permasalahan harus isi.',
            'solusi.required' => 'Solusi harus isi.',
            'kendala.required' => 'Kendala harus isi.',
            ...KpisampahRequest::sampahMessages(),
        ];
    }
}
