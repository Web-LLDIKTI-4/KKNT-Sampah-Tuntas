<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

// Shared bulk edit for Mahasiswa/DPL: prodi, PT, lokasi + nim/nidn collision check
abstract class BulkUpdatePersonRequest extends BulkSelectionRequest
{
    protected ?string $errorMessage = 'Data gagal disimpan!';

    /** @return class-string<Model> */
    abstract protected function model(): string;

    // Unique per PT, same as the single edit form & import
    abstract protected function identityColumn(): string;

    abstract protected function identityLabel(): string;

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'prodi' => ['nullable', 'string', 'max:100'],
            'kodept' => ['nullable', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            'location_program' => ['nullable', 'uuid', 'exists:lokasi_program,id'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'kodept.exists' => 'Perguruan Tinggi tidak valid.',
            'location_program.uuid' => 'Lokasi program tidak valid.',
            'location_program.exists' => 'Lokasi program tidak valid.',
        ];
    }

    // Filled fields only; empty = "-- Tidak diubah --"
    public function fields(): array
    {
        return array_filter($this->safe()->only($this->editableFields()), fn ($value) => $value !== null && $value !== '');
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            // Raw input: validated() here would re-run validation
            $filled = array_filter($this->only($this->editableFields()), fn ($value) => $value !== null && $value !== '');
            if (! $filled) {
                // Key of a form field so the error shows inline
                $validator->errors()->add('location_program', 'Pilih minimal satu field untuk diubah.');

                return;
            }
            if (isset($filled['kodept'])) {
                $this->checkIdentityCollision($validator, $filled['kodept']);
            }
        }];
    }

    private function editableFields(): array
    {
        return array_values(array_diff(array_keys($this->rules()), ['ids', 'ids.*']));
    }

    // Moving rows to another PT must keep nim/nidn unique per PT
    private function checkIdentityCollision(Validator $validator, string $kodept): void
    {
        $model = $this->model();
        $key = (new $model)->getKeyName();
        $column = $this->identityColumn();
        $ids = $this->input('ids');

        $values = $model::whereIn($key, $ids)->whereNotNull($column)->where($column, '<>', '')->pluck($column);
        $duplicates = $values->countBy(fn ($value) => mb_strtolower(trim($value)))->filter(fn ($count) => $count > 1)->keys();
        if ($duplicates->isNotEmpty()) {
            $validator->errors()->add('kodept', $this->identityLabel().' ganda di antara data terpilih: '.$duplicates->take(5)->implode(', ').'.');

            return;
        }

        $taken = $model::where('kodept', $kodept)->whereIn($column, $values)->whereNotIn($key, $ids)->pluck($column);
        if ($taken->isNotEmpty()) {
            $validator->errors()->add('kodept', $this->identityLabel().' sudah terdaftar di PT tujuan: '.$taken->unique()->take(5)->implode(', ').'.');
        }
    }
}
