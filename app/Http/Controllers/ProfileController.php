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
        $user->forceFill(['name' => $request->validated('nama')]);

        // Pemantau (kepala/pemda) and pt store contact on users table
        if ($user->isPemantau() || $user->role === 'pt') {
            $user->forceFill(['phone' => $request->validated('phone')]);
        }

        $user->save();

        if ($user->role === 'dpl') {
            // Campus is locked once set; only an empty kodept can be filled from input
            $existing = Dpl::where('email', $user->email)->first(['kodept']);
            // New DPL record inherits the account's location so both sides match
            Dpl::updateOrCreate(['email' => $user->email], [
                'nidn' => $request->validated('nidn'),
                'nama' => $request->validated('nama'),
                'prodi' => $request->validated('prodi'),
                'kodept' => $existing?->kodept ?: $request->validated('kodept'),
                'phone' => $request->validated('phone'),
            ] + ($existing ? [] : ['location_program' => $user->location_program]));
        }

        return $this->saved('Profile berhasil disimpan');
    }
}
