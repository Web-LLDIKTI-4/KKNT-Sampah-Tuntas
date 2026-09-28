<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Satuanpendidikan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ref_satuanpendidikan';
    protected $primaryKey = 'id_sp';
    public $timestamps = false;
    protected $guarded = [];

     // Accessor to convert tgl_berdiri to a Carbon instance
    public function getLastUpdateAttribute($value)
    {
        return Carbon::createFromFormat('M d Y h:i:s:A', $value);
    }
    
    public function user()
    {
        return $this->hasMany(User::class,'email','npsn');
    }
}