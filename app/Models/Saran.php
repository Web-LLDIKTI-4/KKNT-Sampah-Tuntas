<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
class Saran extends Model
{
    protected $table = 'saran';
    protected $guarded = ['id'];
    protected $primaryKey = 'id'; // Tentukan primary key sesuai dengan struktur tabel

}