<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Satuanpendidikan extends Model
{
    protected $table = 'ref_satuanpendidikan';
    public $timestamps = false;
    protected $guarded = [];

     // Accessor to convert tgl_berdiri to a Carbon instance
     public function getLastUpdateAttribute($value)
     {
         return Carbon::createFromFormat('M d Y h:i:s:A', $value);
     }
 
}