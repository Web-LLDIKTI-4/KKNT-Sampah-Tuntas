<?php

namespace App\Support;

use App\Models\LokasiProgram;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;

/**
 * DataTable server-side mahasiswa/DPL: paginasi di SQL, kolom PT & lokasi tetap bisa dicari/diurutkan.
 */
class PersonDataTable
{
    /** @param  array<int, string>  $columns  kolom tabel utama yang ditampilkan */
    public static function make(Builder $query, array $columns): EloquentDataTable
    {
        $table = $query->getModel()->getTable();
        $select = array_map(fn ($c) => "{$table}.{$c}", [...$columns, 'kodept', 'location_program']);

        $query->select($select)->with(['sp:npsn,nm_lemb', 'locationProgram:id,nama_lokasi']);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('nm_lemb', fn ($row) => $row->sp->nm_lemb ?? 'Belum Terdata')
            ->addColumn('location_program', fn ($row) => $row->locationProgram->nama_lokasi ?? 'Belum Terdata')
            // Id relasi diambil dulu (bukan subquery) agar MySQL memakai index kodept/location_program
            ->filterColumn('nm_lemb', fn ($q, $keyword) => $q->whereIn("{$table}.kodept",
                Satuanpendidikan::where('nm_lemb', 'like', "%{$keyword}%")->pluck('npsn')->all()))
            ->filterColumn('location_program', fn ($q, $keyword) => $q->whereIn("{$table}.location_program",
                LokasiProgram::where('nama_lokasi', 'like', "%{$keyword}%")->pluck('id')->all()))
            ->orderColumn('nm_lemb', fn ($q, $order) => $q->orderBy(
                Satuanpendidikan::select('nm_lemb')->whereColumn('npsn', "{$table}.kodept")->limit(1), $order))
            ->orderColumn('location_program', fn ($q, $order) => $q->orderBy(
                LokasiProgram::select('nama_lokasi')->whereColumn('id', "{$table}.location_program")->limit(1), $order));
    }
}
