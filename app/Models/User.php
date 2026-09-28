<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'location_program',
        'password',
    ];
    protected $with = ['mahasiswa','dpl'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function locationProgram()
    {
        return $this->belongsTo(LokasiProgram::class,'location_program','id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class,'email','email');
    }
    
    public function dpl()
    {
        return $this->belongsTo(Dpl::class,'email','email');
    }

    public function pt()
    {
        return $this->belongsTo(Satuanpendidikan::class,'email','npsn');
    }
}
