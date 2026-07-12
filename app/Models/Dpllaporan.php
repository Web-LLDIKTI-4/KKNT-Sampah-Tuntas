<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Dpllaporan extends Model
{
    protected $table = 'dpl_laporan_bulanan';
    protected $primaryKey = 'id_laporan'; 
    protected $guarded = [];
    public function dpl()
    {
        return $this->hasOne(Dpl::class,'email','email');
    }
    public function user()
    {
        return $this->hasOne(User::class,'email','email');
    }
    
}