<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesProfilePhoto;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Profile\ProfileRequest;
use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ManagesProfilePhoto, RespondsWithJson;

    public function index()
    {
        return view('profile.index');
    }

    public function data(Request $request)
    {
        return view('profile.data', [
            'profile' => $request->user(),
            'dpl' => Dpl::where('email', $request->user()->email)->first(),
            'mahasiswa' => Mahasiswa::where('email', $request->user()->email)->first(),
            'sp' => Satuanpendidikan::orderByRaw('TRIM(nm_lemb) DESC')->get(),
        ]);
    }

    public function update(ProfileRequest $request)
    {
        $user = $request->user();
        $user->forceFill(['name' => $request->validated('nama')])->save();

        if ($user->role === 'dpl') {
            Dpl::updateOrCreate(['email' => $user->email], [
                'nidn' => $request->validated('nidn'),
                'nama' => $request->validated('nama'),
                'prodi' => $request->validated('prodi'),
                'kodept' => $request->validated('kodept'),
                'phone' => $request->validated('phone'),
            ]);
        }

        return $this->saved('Profile berhasil disimpan');
    }
}
