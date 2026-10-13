<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Picdlh extends Model
{
    use HasFactory, HasUuids;

    public const KETERANGAN = [
        'ketua' => 'Ketua',
        'anggota' => 'Anggota',
        'lldikti' => 'LLDIKTI',
    ];

    protected $table = 'pic_dlh';
    protected $primaryKey = 'id_pic_dlh';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = ['id_pic_dlh'];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'id_kecamatan', 'id_kecamatan');
    }
}
