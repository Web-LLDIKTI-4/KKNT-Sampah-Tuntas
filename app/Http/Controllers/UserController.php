<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\DestroyUserRequest;
use App\Http\Requests\Admin\KepalaUserRequest;
use App\Http\Requests\Admin\PtUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UserFilterRequest;
use App\Models\Dpl;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\PersonDeletionService;
use App\Services\UserAccountService;
use App\Support\ActionButtons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    private const PT_FILTER_CACHE = 'user.filter.perguruan_tinggi';

    // Derived columns resolved per role (same as the old Blade list)
    private const ID_NUMBER_SQL = "CASE users.role
        WHEN 'mahasiswa' THEN (SELECT m.nim FROM mahasiswa m WHERE m.email = users.email LIMIT 1)
        WHEN 'dpl' THEN (SELECT d.nidn FROM dpl d WHERE d.email = users.email LIMIT 1) END";

    private const PT_NAME_SQL = "CASE users.role
        WHEN 'mahasiswa' THEN (SELECT COALESCE(NULLIF(sp.nm_lemb, ''), m.kodept) FROM mahasiswa m LEFT JOIN ref_satuanpendidikan sp ON sp.npsn = m.kodept WHERE m.email = users.email LIMIT 1)
        WHEN 'dpl' THEN (SELECT COALESCE(NULLIF(sp.nm_lemb, ''), d.kodept) FROM dpl d LEFT JOIN ref_satuanpendidikan sp ON sp.npsn = d.kodept WHERE d.email = users.email LIMIT 1)
        WHEN 'pt' THEN (SELECT sp.nm_lemb FROM ref_satuanpendidikan sp WHERE sp.npsn = users.email LIMIT 1) END";

    public function __construct(private UserAccountService $accounts) {}

    public function index()
    {
        // Only PTs that have at least one user account
        $perguruanTinggi = collect(Cache::remember(self::PT_FILTER_CACHE, now()->addMinutes(10), function () {
            $kodept = Mahasiswa::whereHas('user')->distinct()->pluck('kodept')
                ->merge(Dpl::whereHas('user')->distinct()->pluck('kodept'))
                ->merge(User::where('role', 'pt')->pluck('email'))
                ->filter()->unique()->values();

            return Satuanpendidikan::whereIn('npsn', $kodept)->orderBy('nm_lemb')->pluck('nm_lemb', 'npsn')->all();
        }));

        return view('user.index', ['perguruanTinggi' => $perguruanTinggi]);
    }

    public function listdata(UserFilterRequest $request)
    {
        return view('user.list', ['filters' => array_filter($request->validated())]);
    }

    public function listdataserver(UserFilterRequest $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();
        $whitelist = ['email', 'name', 'id_number', 'pt_name', 'role'];

        $query = User::managed($request->validated())
            ->select(['users.id', 'users.email', 'users.name', 'users.role', 'users.akses'])
            ->when(! $this->hasClientOrder($whitelist), fn ($q) => $q->orderBy('users.name'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('id_number', fn (User $row) => match (true) {
                $row->role === 'mahasiswa' && $row->mahasiswa => $row->mahasiswa->nim,
                $row->role === 'dpl' && $row->dpl => $row->dpl->nidn,
                default => '-',
            })
            ->addColumn('pt_name', fn (User $row) => match (true) {
                $row->role === 'mahasiswa' && $row->mahasiswa => $row->mahasiswa->sp?->nm_lemb ?: $row->mahasiswa->kodept,
                $row->role === 'dpl' && $row->dpl => $row->dpl->sp?->nm_lemb ?: $row->dpl->kodept,
                $row->role === 'pt' && $row->pt => $row->pt->nm_lemb,
                default => '-',
            })
            ->addColumn('action', fn (User $row) => ActionButtons::make(
                urlEdit: url(match ($row->role) {
                    'kepala', 'pemda' => 'user/edituserkepala/',
                    'pt' => 'user/edituserpt/',
                    default => 'user/edit/',
                }.$row->id),
                urlDelete: url('user/destroy'),
                idValue: $row->id,
                confirm: $row->akses === 'pjdesa'
                    ? 'User ini ketua kelompok. Capaian KPI dan data pengurangan sampah kelompok/desa ikut terhapus. Lanjutkan? Tindakan ini tidak dapat dibatalkan.'
                    : 'Hapus user ini beserta SEMUA datanya? Tindakan ini tidak dapat dibatalkan.',
            ))
            // Derived columns: search & sort via the same per-role subqueries
            ->filterColumn('id_number', fn ($q, $k) => $q->whereRaw('('.self::ID_NUMBER_SQL.') LIKE ?', ["%{$k}%"]))
            ->orderColumn('id_number', fn ($q, $dir) => $q->orderByRaw('('.self::ID_NUMBER_SQL.') '.$dir))
            ->filterColumn('pt_name', fn ($q, $k) => $q->whereRaw('('.self::PT_NAME_SQL.') LIKE ?', ["%{$k}%"]))
            ->orderColumn('pt_name', fn ($q, $dir) => $q->orderByRaw('('.self::PT_NAME_SQL.') '.$dir))
            ->removeColumn('mahasiswa', 'dpl', 'pt')
            ->rawColumns(['action'])
            ->whitelist($whitelist)
            ->make(true);
    }

    public function getdatamember()
    {
        return view('user.listmember', ['data' => Mahasiswa::select(['id_mahasiswa', 'nim', 'nama', 'email', 'kodept', 'location_program'])
            ->with(['sp:npsn,nm_lemb', 'locationProgram:id,nama_lokasi'])->whereDoesntHave('user')->get()]);
    }

    public function insert(Request $request)
    {
        $emails = $this->selectedEmails($request);
        if (! $emails) {
            return response()->json(['error' => 'user harus dipilih']);
        }

        $count = $this->accounts->createForMahasiswa($emails);
        Cache::forget(self::PT_FILTER_CACHE);

        return response()->json(['success' => $count.' user berhasil dibuat']);
    }

    public function adduser()
    {
        return view('user.listdpl', ['data' => Dpl::select(['id_dpl', 'nidn', 'nama', 'prodi', 'email', 'kodept', 'location_program'])
            ->with(['sp:npsn,nm_lemb', 'locationProgram:id,nama_lokasi'])->whereDoesntHave('user')->get()]);
    }

    public function insertuser(Request $request)
    {
        $emails = $this->selectedEmails($request);
        if (! $emails) {
            return response()->json(['error' => 'user harus dipilih']);
        }

        $count = $this->accounts->createForDpl($emails);
        Cache::forget(self::PT_FILTER_CACHE);

        return response()->json(['success' => $count.' user berhasil dibuat']);
    }

    public function edit(string $id)
    {
        return view('user.edit', [
            'data' => User::whereIn('role', ['mahasiswa', 'dpl'])->findOrFail($id),
            'akses' => ['pjdesa' => 'Set Ketua Kelompok', 'hapuspjdesa' => 'Hapus Akses Ketua Kelompok'],
            'locationPrograms' => LokasiProgram::orderBy('nama_lokasi')->get(),
        ]);
    }

    public function updateuser(UpdateUserRequest $request)
    {
        $user = User::findOrFail($request->validated('id'));
        $role = $user->role;
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
            'phone' => $request->validated('phone'),
            'role' => 'pt',
            'password' => Hash::make($request->validated('password')),
        ])->save();
        Cache::forget(self::PT_FILTER_CACHE);

        return $this->saved('user berhasil dibuat');
    }

    public function edituserpt(string $id)
    {
        return view('user.edit_pt', [
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
            'phone' => $request->validated('phone'),
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        User::where('role', 'pt')->findOrFail($request->validated('id'))->forceFill($data)->save();
        Cache::forget(self::PT_FILTER_CACHE);

        return $this->saved('user berhasil diupdate');
    }

    public function adduserkepala()
    {
        return view('user.form_kepala', ['user' => null]);
    }

    public function insertuserkepala(KepalaUserRequest $request)
    {
        // Role dibatasi kepala/pemda lewat KepalaUserRequest
        (new User)->forceFill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return $this->saved('User '.$request->validated('role').' berhasil dibuat');
    }

    public function edituserkepala(string $id)
    {
        return view('user.form_kepala', ['user' => User::whereIn('role', ['kepala', 'pemda'])->findOrFail($id)]);
    }

    public function updateuserkepala(KepalaUserRequest $request)
    {
        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        User::whereIn('role', ['kepala', 'pemda'])->findOrFail($request->validated('id'))->forceFill($data)->save();

        return $this->saved('User '.$request->validated('role').' berhasil diupdate');
    }

    public function destroy(DestroyUserRequest $request, PersonDeletionService $deletion)
    {
        $user = User::findOrFail($request->validated('id'));

        try {
            $deletion->deleteUser($user);
        } catch (Throwable $e) {
            Log::error('Hapus user gagal', ['user_id' => $user->id, 'exception' => $e]);

            return $this->failed('Data gagal dihapus');
        }
        Cache::forget(self::PT_FILTER_CACHE);

        return $this->deleted();
    }

    public function export(UserFilterRequest $request)
    {
        $export = new UserExport($request->validated());

        return response()->streamDownload(fn () => $export->writeTo('php://output'), 'users_'.date('Y-m-d_H-i-s').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function selectedEmails(Request $request): array
    {
        $emails = array_values(array_filter((array) $request->input('createuser', []), 'is_string'));

        return array_slice($emails, 0, 1000);
    }
}
