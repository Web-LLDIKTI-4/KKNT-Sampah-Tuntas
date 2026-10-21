<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PelaksanaanKkn extends Model
{
    use HasFactory, HasUuids;

    // Role yang boleh melihat daftar semua PT (read-only)
    public const VIEWER_ROLES = ['admin', 'kepala', 'pemda'];

    protected $table = 'pelaksanaan_kkn';
    protected $primaryKey = 'id_pelaksanaan';
    protected $keyType = 'string';
    public $incrementing = false;

    // kodept & updated_by sengaja tidak fillable: diisi server, cegah mass assignment
    protected $fillable = [
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    // Durasi inklusif: 1-3 Jan = 3 hari
    protected function durasiHari(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
                return null;
            }

            return (int) $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
        });
    }

    public function pt()
    {
        return $this->belongsTo(Satuanpendidikan::class, 'kodept', 'npsn');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
