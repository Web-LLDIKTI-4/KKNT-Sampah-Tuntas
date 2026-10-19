<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Mahasiswa\IzinRequest;
use App\Http\Requests\Mahasiswa\KehadiranRequest;
use App\Models\Kehadiran;
use App\Services\AttendanceService;
use App\Support\ActionButtons;
use App\Support\XlsxStreamer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LogkehadiranController extends Controller
{
    use HandlesDataTablesOrder;
    use RespondsWithJson;

    private const BADGE = [
        'hadir' => ['bg-success', 'Hadir'],
        'izin' => ['bg-warning', 'Izin'],
        'sakit' => ['bg-danger', 'Sakit'],
        'cuti' => ['bg-info', 'Cuti'],
        'kuliah' => ['bg-primary', 'Kuliah'],
        'libur nasional' => ['bg-dark', 'Libur Nasional'],
    ];

    public static function badge(?string $status): string
    {
        [$class, $label] = self::BADGE[$status] ?? ['bg-secondary', 'Belum Absen'];

        return '<span class="badge '.$class.'">'.$label.'</span>';
    }

    public function __construct(private AttendanceService $attendance) {}

    public function index()
    {
        return view('kehadiran.index');
    }

    public function listdata()
    {
        return view('kehadiran.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        $this->sanitizeDataTablesInput();

        $data = Kehadiran::ownedBy($request->user())
            ->without(['mahasiswa', 'dplmentoring'])
            ->orderByDesc('tanggal')
            ->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('status_kehadiran', fn (Kehadiran $row) => self::badge($row->status_kehadiran))
            ->editColumn('tanggal', fn (Kehadiran $row) => $row->tanggal ? date('d-m-Y', strtotime($row->tanggal)) : '-')
            ->editColumn('waktu_masuk', fn (Kehadiran $row) => $row->waktu_masuk ? date('H:i:s', strtotime($row->waktu_masuk)).' WIB' : '-')
            ->editColumn('waktu_pulang', fn (Kehadiran $row) => $row->waktu_pulang ? date('H:i:s', strtotime($row->waktu_pulang)).' WIB' : '-')
            ->addColumn('coordinates_datang', fn (Kehadiran $row) => ActionButtons::map($row->latitude_datang, $row->longitude_datang))
            ->addColumn('coordinates_pulang', fn (Kehadiran $row) => ActionButtons::map($row->latitude_pulang, $row->longitude_pulang))
            ->addColumn('action', '')
            ->rawColumns(['status_kehadiran', 'coordinates_datang', 'coordinates_pulang'])
            ->make(true);
    }

    public function tambah(Request $request)
    {
        return view('kehadiran.tambah', ['data' => $this->today($request)]);
    }

    public function insert(KehadiranRequest $request)
    {
        $mode = $request->validated('mode');
        $today = $this->today($request);
        [$lat, $lng] = $request->coordinates();

        $reason = $this->attendance->rejectReason($today, $mode)
            ?? ($this->attendance->withinRadius($lat, $lng) ? null : 'Lokasi Anda di luar radius absensi yang diizinkan.');
        if ($reason) {
            return $request->expectsJson() ? $this->failed($reason) : back()->with('error', $reason);
        }

        $column = $mode === 'datang' ? 'masuk' : 'pulang';
        Kehadiran::updateOrCreate(
            ['email' => $request->user()->email, 'tanggal' => today()->toDateString()],
            [
                'waktu_'.$column => now(),
                'latitude_'.$mode => $lat,
                'longitude_'.$mode => $lng,
                'status_kehadiran' => 'hadir',
            ]
        );

        $message = ($today ? 'Data kehadiran berhasil diupdate' : 'Data kehadiran berhasil ditambahkan')
            .', anda melakukan absensi pukul '.now()->format('H:i:s');

        return $request->expectsJson() ? $this->saved($message) : back()->with('success', $message);
    }

    public function tambahizin()
    {
        return view('kehadiran.tambahizin', ['status_kehadiran' => AttendanceService::IZIN_STATUSES]);
    }

    public function insertizin(IzinRequest $request)
    {
        Kehadiran::create([
            'tanggal' => today()->toDateString(),
            'email' => $request->user()->email,
            'status_kehadiran' => $request->validated('status_kehadiran'),
            'keterangan' => $request->validated('keterangan'),
        ]);

        return $this->saved('Laporan izin berhasil disimpan.');
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $time = fn ($value) => $value ? Carbon::parse($value)->format('H:i:s') : null;
        $rows = Kehadiran::ownedBy($user)
            ->without(['mahasiswa', 'dplmentoring'])
            ->orderBy('tanggal')
            ->orderBy('id_kehadiran')
            ->lazy(1000);

        return (new XlsxStreamer('Log Kehadiran Mahasiswa', [
            XlsxStreamer::column('Tanggal', 'tanggal', 12, 'date'),
            XlsxStreamer::column('Status Kehadiran', fn (Kehadiran $row) => ucfirst((string) $row->status_kehadiran), 18),
            XlsxStreamer::column('Jam Masuk', fn (Kehadiran $row) => $time($row->waktu_masuk), 12),
            XlsxStreamer::column('Jam Pulang', fn (Kehadiran $row) => $time($row->waktu_pulang), 12),
        ]))
            ->info(['Mahasiswa' => $user->name, 'Email' => $user->email])
            ->download('kehadiran_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx', $rows);
    }

    private function today(Request $request): ?Kehadiran
    {
        return Kehadiran::ownedBy($request->user())->whereDate('tanggal', today())->first();
    }
}
