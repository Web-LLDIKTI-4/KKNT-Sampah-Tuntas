<?php

namespace App\Support;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Facades\DataTables;

/**
 * DataTable server-side daftar user (mahasiswa/dpl/pt/kepala); tampilan sama dengan user/list.blade.php.
 */
class UserDataTable
{
    public const ROLES = ['mahasiswa', 'dpl', 'pt', 'kepala'];

    public static function make(): EloquentDataTable
    {
        $query = User::query()
            ->select('users.id', 'users.email', 'users.name', 'users.role')
            ->whereIn('users.role', self::ROLES)
            ->with(['mahasiswa:email,nim,kodept', 'mahasiswa.sp:npsn,nm_lemb', 'dpl:email,nidn,kodept', 'dpl.sp:npsn,nm_lemb', 'pt:npsn,nm_lemb']);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('nim_nidn', fn (User $row) => match (true) {
                $row->role === 'mahasiswa' && $row->mahasiswa => $row->mahasiswa->nim,
                $row->role === 'dpl' && $row->dpl => $row->dpl->nidn,
                default => '-',
            })
            ->addColumn('nm_lemb', fn (User $row) => match (true) {
                $row->role === 'mahasiswa' && $row->mahasiswa => $row->mahasiswa->sp->nm_lemb ?? $row->mahasiswa->kodept,
                $row->role === 'dpl' && $row->dpl => $row->dpl->sp->nm_lemb ?? $row->dpl->kodept,
                $row->role === 'pt' && $row->pt => $row->pt->nm_lemb,
                default => '-',
            })
            ->addColumn('action', fn (User $row) => ActionButtons::make(urlEdit: url(match ($row->role) {
                'kepala' => 'user/edituserkepala/',
                'pt' => 'user/edituserpt/',
                default => 'user/edit/',
            }.$row->id)))
            ->filterColumn('nim_nidn', fn ($q, $keyword) => $q->where(fn (Builder $w) => $w
                ->where(fn ($m) => $m->where('users.role', 'mahasiswa')
                    ->whereIn('users.email', Mahasiswa::select('email')->where('nim', 'like', "%{$keyword}%")))
                ->orWhere(fn ($d) => $d->where('users.role', 'dpl')
                    ->whereIn('users.email', Dpl::select('email')->where('nidn', 'like', "%{$keyword}%")))))
            ->filterColumn('nm_lemb', function ($q, $keyword) {
                // npsn diambil dulu agar subquery tidak dependent per baris user (hasil EXPLAIN)
                $npsn = Satuanpendidikan::where('nm_lemb', 'like', "%{$keyword}%")->pluck('npsn')->all();

                $q->where(fn (Builder $w) => $w
                    ->where(fn ($m) => $m->where('users.role', 'mahasiswa')
                        ->whereIn('users.email', Mahasiswa::select('email')->whereIn('kodept', $npsn)))
                    ->orWhere(fn ($d) => $d->where('users.role', 'dpl')
                        ->whereIn('users.email', Dpl::select('email')->whereIn('kodept', $npsn)))
                    ->orWhere(fn ($p) => $p->where('users.role', 'pt')->whereIn('users.email', $npsn)));
            })
            // Kolom turunan per role (bukan kolom SQL): order diabaikan agar tidak 500
            ->orderColumn('nim_nidn', false)
            ->orderColumn('nm_lemb', false)
            ->rawColumns(['action']);
    }
}
