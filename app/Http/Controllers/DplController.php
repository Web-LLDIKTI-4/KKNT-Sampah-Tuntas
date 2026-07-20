<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Kehadiran;
use App\Models\Logkegiatan;
use App\Models\Dpllaporan;
use App\Models\User;
use App\Models\Nilaikonversi;
use App\Models\Tugasakhir;

use DataTables;
use App\Imports\DPLImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class DplController extends Controller
{
    public function index()
    {  
        return view('dpl.index');
    }
    public function listdata()
    {
        return view('dpl.listdata');
    }
    public function listdataserver(Request $request)
    {
        if ($request->ajax()) {
            $data = Dpl::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nm_lemb', function($row) {
                    return $row->sp->nm_lemb ?? 'Belum Terdata';
                })
                ->addColumn('location_program', function($row) {
                    return $row->locationProgram->nama_lokasi ?? 'Belum Terdata';
                })
                ->addColumn('action', function($row){
                    return view('components.action-data', [
                        'urlDelete' => url('dpl/destroy'),
                        'idField' => 'id_dpl',
                        'idValue' => $row->id_dpl,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
            }
    }
    public function import(){
        return view('dpl.import');
    }

    public function prosesimport(Request $request)
    {
        try {
            $import = new DPLImport;
            Excel::import($import, $request->file);

            if (count($import->errors) > 0) {
                // ada baris yang gagal/di-skip
                return back()
                    ->with('warning', "{$import->imported} data berhasil diimpor.")
                    ->with('import_errors', $import->errors);
            }

            session()->flash('success', 'Data DPL berhasil diimpor.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage());
        }

        return back();
    }

    public function destroy(Request $request){
        try {
            DB::beginTransaction();
        
            $dpl = Dpl::find($request->id_dpl);
            if (!$dpl) {
                return response()->json(['success' => false, 'message' => 'DPL gagal ditemukan: ' . $e->getMessage()], 200);
            }

            // Hapus semua data terkait DPL
            Dplmentoring::where("email_dpl", $dpl->email)->delete();
            Kehadiran::where("email", $dpl->email)->delete();
            Dpllaporan::where("email", $dpl->email)->delete();
            User::where("email", $dpl->email)->delete();
            Nilaikonversi::where("email_dpl", $dpl->email)->delete();
            Tugasakhir::where("email_dpl", $dpl->email)->delete();

            // Hapus DPL
            $dpl->delete();
        
            DB::commit();
        
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
        
            return response()->json(['success' => false, 'message' => 'Data gagal dihapus: ' . $e->getMessage()], 200);
        }

    }
}
