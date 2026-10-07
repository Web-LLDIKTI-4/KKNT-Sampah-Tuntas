<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RencanaKerja extends Model
{
    use HasFactory, HasUuids;

    // File disimpan privat; akses hanya lewat route download
    public const DISK = 'local';

    // Role yang boleh melihat daftar; pt hanya miliknya, sisanya semua PT
    public const VIEWER_ROLES = ['admin', 'pt', 'kepala', 'pemda', 'dpl', 'mahasiswa'];

    protected $table = 'rencana_kerja';
    protected $primaryKey = 'id_rencana_kerja';
    protected $keyType = 'string';
    public $incrementing = false;

    // kodept & uploaded_by sengaja tidak fillable: diisi server, cegah mass assignment
    protected $fillable = [
        'judul',
        'tahun',
        'keterangan',
        'file_path',
        'nama_file',
        'ukuran',
        'mime',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'ukuran' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (RencanaKerja $rencanaKerja) {
            if ($rencanaKerja->file_path && Storage::disk(self::DISK)->exists($rencanaKerja->file_path)) {
                Storage::disk(self::DISK)->delete($rencanaKerja->file_path);
            }
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === 'pt') {
            return $query->where('kodept', $user->email);
        }

        return in_array($user->role, self::VIEWER_ROLES, true) ? $query : $query->whereRaw('1 = 0');
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
