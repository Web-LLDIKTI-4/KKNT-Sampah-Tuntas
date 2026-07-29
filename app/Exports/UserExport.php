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

            $dataPt = null;

            if ($data->role === 'mahasiswa') {
                $dataPt = $data?->mahasiswa?->sp?->nm_lemb;
            } elseif ($data->role === 'dpl') {
                $dataPt = $data?->dpl?->sp?->nm_lemb;
            } else {
                $dataPt = $data?->pt?->nm_lemb;
            }

            return [
                'No' => $key + 1,
                'Nama' => $data->name,
                'Email' => $data->email,
                'Perguruan Tinggi' => $dataPt,
                'Role' => $data->role,
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nama',
            'Email',
            'Perguruan Tinggi',
            'Role',
        ];
    }
}
