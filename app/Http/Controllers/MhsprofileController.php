<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesProfilePhoto;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Profile\MahasiswaProfileRequest;
use App\Http\Requests\Profile\SetLokasiRequest;
use App\Models\Kecamatan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Satuanpendidikan;
use Illuminate\Http\Request;

class MhsprofileController extends Controller
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
            'mahasiswa' => Mahasiswa::where('email', $request->user()->email)->first(),
            'dpl' => null,
            'sp' => Satuanpendidikan::orderBy('nm_lemb')->get(),
        ]);
    }

    public function update(MahasiswaProfileRequest $request)
    {
        $mahasiswa = Mahasiswa::where('email', $request->user()->email)->first();
        if (! $mahasiswa) {
            return $this->notFound();
        }

        $mahasiswa->update($request->safe()->only('nama', 'nim', 'kodept', 'prodi', 'phone', 'tahun_masuk'));

        return $this->saved('Profile berhasil disimpan');
    }

    public function formlokasi()
    {
        return view('profile.lokasi', [
            'desa' => Kecamatan::with(['desa' => fn ($q) => $q->orderBy('desa')])->orderBy('kecamatan')->get(),
        ]);
    }

    public function setlokasi(SetLokasiRequest $request)
    {
        $user = $request->user();
        $lokasi = Mahasiswa_lokasi::updateOrCreate(
            ['id_mahasiswa' => $user->mahasiswa->id_mahasiswa, 'tahun' => $request->validated('tahun')],
            ['id_desa' => $request->validated('id_desa'), 'user_in_up' => $user->email]
        );

        return $this->saved($lokasi->wasRecentlyCreated ? 'Lokasi berhasil disimpan' : 'Lokasi berhasil diperbarui');
    }
}
