<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\KepalaUserRequest;
use App\Http\Requests\Admin\PtUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Dpl;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    use RespondsWithJson;

    public function __construct(private UserAccountService $accounts) {}

    public function index()
    {
        return view('user.index');
    }

    public function listdata()
    {
        return view('user.list', ['data' => User::whereIn('role', ['mahasiswa', 'dpl', 'pt', 'kepala'])->get()]);
    }

    public function getdatamember()
    {
        return view('user.listmember', ['data' => Mahasiswa::with('sp')->whereDoesntHave('user')->get()]);
    }

    public function insert(Request $request)
    {
        $emails = $this->selectedEmails($request);
        if (! $emails) {
            return response()->json(['error' => 'user harus dipilih']);
        }

        $count = $this->accounts->createForMahasiswa($emails);

        return response()->json(['success' => $count.' user berhasil dibuat']);
    }

    public function adduser()
    {
        return view('user.listdpl', ['data' => Dpl::with('sp')->whereDoesntHave('user')->get()]);
    }

    public function insertuser(Request $request)
    {
        $emails = $this->selectedEmails($request);
        if (! $emails) {
            return response()->json(['error' => 'user harus dipilih']);
        }

        $count = $this->accounts->createForDpl($emails);

        return response()->json(['success' => $count.' user berhasil dibuat']);
    }

    public function edit(string $id)
    {
        return view('user.edit', [
            'data' => User::whereIn('role', ['mahasiswa', 'dpl'])->findOrFail($id),
            'role' => ['mahasiswa', 'dpl'],
            'akses' => ['pjdesa' => 'Set Ketua Kelompok', 'hapuspjdesa' => 'Hapus Akses Ketua Kelompok'],
            'locationPrograms' => LokasiProgram::orderBy('nama_lokasi')->get(),
        ]);
    }

    public function updateuser(UpdateUserRequest $request)
    {
        $user = User::findOrFail($request->validated('id'));
        $role = $request->validated('role');
        $akses = $request->validated('akses');
        $mahasiswa = Mahasiswa::where('email', $user->email)->first();

        $lokasiDesa = $mahasiswa
            ? Mahasiswa_lokasi::where('id_mahasiswa', $mahasiswa->id_mahasiswa)->orderByDesc('tahun')->value('id_desa')
            : null;
        if ($akses === 'pjdesa' && $role === 'mahasiswa' && ! $lokasiDesa) {
            return $this->failed('Tidak dapat set sebagai ketua kelompok, mahasiswa harus set lokasi kegiatan KKN terlebih dahulu!');
        }

        DB::transaction(function () use ($request, $user, $role, $akses, $lokasiDesa) {
            $this->accounts->changeEmail($user, $request->validated('email'));
            $email = $user->email;

            $data = [
                'name' => $request->validated('name'),
                'role' => $role,
                'location_program' => $request->validated('location_program'),
            ];
            if ($role === 'mahasiswa' && $data['location_program']) {
                Mahasiswa::where('email', $email)->update(['location_program' => $data['location_program']]);
            }

            if ($akses && $role === 'mahasiswa') {
                if ($akses === 'hapuspjdesa') {
                    Pjdesa::where('email', $email)->delete();
                    $data['akses'] = null;
                } else {
                    Pjdesa::updateOrCreate(['email' => $email], ['id_desa' => $lokasiDesa]);
                    $data['akses'] = $akses;
                }
            }

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->validated('password'));
            }

            $user->forceFill($data)->save();
        });

        return $this->saved('User berhasil diupdate');
    }

    public function adduserpt()
    {
        return view('user.tambah_pt', [
            'role' => ['pt'],
            'sp' => Satuanpendidikan::orderBy('nm_lemb')->get(),
            'locationPrograms' => LokasiProgram::orderBy('nama_lokasi')->get(),
        ]);
    }

    public function insertuserpt(PtUserRequest $request)
    {
        // Role dikunci "pt", tidak diambil dari input
        (new User)->forceFill([
            'name' => $request->validated('name'),
            'email' => $request->validated('kodept'),
            'location_program' => $request->validated('location_program'),
            'role' => 'pt',
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return $this->saved('user berhasil dibuat');
    }

    public function edituserpt(string $id)
    {
        return view('user.edit_pt', [
            'role' => ['pt'],
            'user' => User::where('role', 'pt')->findOrFail($id),
            'sp' => Satuanpendidikan::orderByRaw('TRIM(nm_lemb) DESC')->get(),
            'locationPrograms' => LokasiProgram::orderBy('nama_lokasi')->get(),
        ]);
    }

    public function updateuserpt(PtUserRequest $request)
    {
        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('kodept'),
            'location_program' => $request->validated('location_program'),
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        User::where('role', 'pt')->findOrFail($request->validated('id'))->forceFill($data)->save();

        return $this->saved('user berhasil diupdate');
    }

    public function adduserkepala()
    {
        return view('user.form_kepala', ['user' => null]);
    }

    public function insertuserkepala(KepalaUserRequest $request)
    {
        // Role dikunci "kepala", tidak diambil dari input
        (new User)->forceFill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => 'kepala',
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return $this->saved('User kepala berhasil dibuat');
    }

    public function edituserkepala(string $id)
    {
        return view('user.form_kepala', ['user' => User::where('role', 'kepala')->findOrFail($id)]);
    }

    public function updateuserkepala(KepalaUserRequest $request)
    {
        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        User::where('role', 'kepala')->findOrFail($request->validated('id'))->forceFill($data)->save();

        return $this->saved('User kepala berhasil diupdate');
    }

    public function export()
    {
        return Excel::download(new UserExport, 'users_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    private function selectedEmails(Request $request): array
    {
        $emails = array_values(array_filter((array) $request->input('createuser', []), 'is_string'));

        return array_slice($emails, 0, 1000);
    }
}
