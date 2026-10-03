<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;

/**
 * DataTable server-side nilai per mahasiswa (konversi/freeform): kolom mahasiswa & PT lewat join.
 */
class NilaiMahasiswaDataTable
{
    public static function make(Builder $query): EloquentDataTable
    {
        $table = $query->getModel()->getTable();

        // Kolom join ber-prefix agar order DataTables memetakan ke tabel yang benar
        $query->select("{$table}.*", 'mahasiswa.nim', 'mahasiswa.nama', 'mahasiswa.prodi', 'ref_satuanpendidikan.nm_lemb')
            ->leftJoin('mahasiswa', 'mahasiswa.id_mahasiswa', '=', "{$table}.id_mahasiswa")
            ->leftJoin('ref_satuanpendidikan', 'ref_satuanpendidikan.npsn', '=', 'mahasiswa.kodept');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('nim', fn ($row) => $row->nim ?? '-')
            ->editColumn('nama', fn ($row) => $row->nama ?? '-')
            ->editColumn('nm_lemb', fn ($row) => $row->nm_lemb ?? '-')
            ->editColumn('prodi', fn ($row) => $row->prodi ?? '-')
            ->addColumn('nilai_akhir', fn ($row) => ((is_numeric($row->nilai_dpl) ? $row->nilai_dpl : 0)
                + (is_numeric($row->nilai_dpa) ? $row->nilai_dpa : 0)) / 2)
            ->filterColumn('nim', fn ($q, $keyword) => $q->where('mahasiswa.nim', 'like', "%{$keyword}%"))
            ->filterColumn('nama', fn ($q, $keyword) => $q->where('mahasiswa.nama', 'like', "%{$keyword}%"))
            ->filterColumn('prodi', fn ($q, $keyword) => $q->where('mahasiswa.prodi', 'like', "%{$keyword}%"))
            ->filterColumn('nm_lemb', fn ($q, $keyword) => $q->where('ref_satuanpendidikan.nm_lemb', 'like', "%{$keyword}%"))
            ->filterColumn('nilai_akhir', fn () => null)
            ->orderColumn('nilai_akhir', "(COALESCE({$table}.nilai_dpl, 0) + COALESCE({$table}.nilai_dpa, 0)) \$1");
    }
}
