<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RealisasiKerja extends Model
{
    use HasFactory, HasUuids;

    // File disimpan privat; akses hanya lewat route download
    public const DISK = 'local';

    // Role yang boleh melihat daftar
    public const VIEWER_ROLES = ['admin', 'pt', 'kepala', 'pemda', 'dpl', 'mahasiswa'];

    // Role yang melihat semua PT; role viewer lain hanya PT sendiri (ptKodept)
    public const ALL_PT_ROLES = ['admin', 'kepala', 'pemda'];

    protected $table = 'realisasi_kerja';
    protected $primaryKey = 'id_realisasi_kerja';
    protected $keyType = 'string';
    public $incrementing = false;

    // kodept & uploaded_by sengaja tidak fillable: diisi server, cegah mass assignment
    protected $fillable = [
        'judul',
        'keterangan',
        'file_path',
        'nama_file',
        'ukuran',
        'mime',
    ];

    protected $casts = [
        'ukuran' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (RealisasiKerja $realisasiKerja) {
            if ($realisasiKerja->file_path && Storage::disk(self::DISK)->exists($realisasiKerja->file_path)) {
                Storage::disk(self::DISK)->delete($realisasiKerja->file_path);
            }
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (in_array($user->role, self::ALL_PT_ROLES, true)) {
            return $query;
        }

        $kodept = in_array($user->role, self::VIEWER_ROLES, true) ? $user->ptKodept() : null;

        // Fail-closed: tanpa kodept tidak ada data
        return $kodept === null ? $query->whereRaw('1 = 0') : $query->where('kodept', $kodept);
    }

    public function pt()
    {
        return $this->belongsTo(Satuanpendidikan::class, 'kodept', 'npsn');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }
}
