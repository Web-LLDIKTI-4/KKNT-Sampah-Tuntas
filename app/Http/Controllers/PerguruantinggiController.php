<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\KodeptRequest;
use App\Models\Satuanpendidikan;
use App\Services\PddiktiService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PerguruantinggiController extends Controller
{
    public function __construct(private PddiktiService $pddikti) {}

    public function index()
    {
        return view('perguruantinggi.index');
    }

    public function listdata()
    {
        return view('perguruantinggi.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Satuanpendidikan::query())
            ->addIndexColumn()
            ->addColumn('action', '')
            ->make(true);
    }

    public function getdata()
    {
        $rows = $this->pddikti->allLldikti4();
        if ($rows === null) {
            return response()->json(['success' => false, 'messages' => 'Koneksi ke PDDIKTI error!', 'errors' => 'Koneksi ke PDDIKTI error!']);
        }

        $count = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $count[$this->pddikti->upsert((array) $row)]++;
        }

        return response()->json([
            'success' => true,
            'messages' => $count['updated'].' Data berhasil di update dan '.$count['created'].' Data berhasil disimpan!',
        ]);
    }

    public function tambah()
    {
        return view('perguruantinggi.tambah');
    }

    public function insert(KodeptRequest $request)
    {
        $result = $this->pddikti->findByKodept($request->validated('kodept'));
        if (! $result) {
            return response()->json(['success' => false, 'messages' => 'Koneksi ke PDDIKTI error']);
        }

        // API bisa mengembalikan satu objek atau daftar
        $row = array_is_list($result) ? ($result[0] ?? []) : $result;
        $status = $this->pddikti->upsert((array) $row);

        return match ($status) {
            'created' => response()->json(['success' => true, 'messages' => 'Data perguruan tinggi berhasil dimasukkan']),
            'updated' => response()->json(['success' => true, 'messages' => 'Data perguruan tinggi berhasil diupdate']),
            default => response()->json(['success' => false, 'messages' => 'Data perguruan tinggi tidak ditemukan di PDDIKTI']),
        };
    }
}
