<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

use App\Models\User;

class UserExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $users = User::whereNotIn('role', ['admin'])->get();

        $data = $users->map(function ($data, $key) {

            $dataPt     = null;
            $dataNim    = null;

            if ($data->role === 'mahasiswa') {
                $dataNim = $data?->mahasiswa?->nim;
                $dataPt = $data?->mahasiswa?->sp?->nm_lemb;
            } elseif ($data->role === 'dpl') {
                $dataNim = $data?->dpl?->nidn;
                $dataPt = $data?->dpl?->sp?->nm_lemb;
            } else {
                $dataNim = $data?->pt?->npsn;
                $dataPt = $data?->pt?->nm_lemb;
            }

            return [
                'No' => $key + 1,
                'Nama' => $data->name,
                'NIM/NIDN/Kode PT' => $dataNim,
                'Email' => $data->email,
                'Perguruan Tinggi' => $dataPt,
                'Role' => ucfirst($data->role),
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nama',
            'NIM/NIDN/Kode PT',
            'Email',
            'Perguruan Tinggi',
            'Role',
        ];
    }
}
