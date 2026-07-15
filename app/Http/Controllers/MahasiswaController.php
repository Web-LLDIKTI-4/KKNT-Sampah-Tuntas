<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Kehadiran;
use App\Models\Dplmentoring;
use App\Models\Logkegiatan;
use App\Models\Logbulanan;
use DataTables;
use App\Imports\ImportMahasiswa;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class MahasiswaController extends Controller
{    
    public function index()
    {  
        return view('mahasiswa.index');
    }
    public function listdata()
    {
        return view('mahasiswa.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Mahasiswa::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nm_lemb', function($row) {
                    return $row->sp->nm_lemb ?? 'Belum terdata';
                })
                ->addColumn('location_program', function($row) {
                    return $row->locationProgram->nama_lokasi ?? 'Belum terdata';
                })
                ->addColumn('action', function($row){
                    return view('components.btn-delete', [
                        'url' => url('mahasiswa/destroy'),
                        'idField' => 'id_mahasiswa',
                        'idValue' => $row->id_mahasiswa,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
            }
    }
    public function import(){
        return view('mahasiswa.import');
    }

    public function prosesimport(Request $request)
    {
        try {
            $import = new ImportMahasiswa;
            Excel::import($import, $request->file);

            if (count($import->errors) > 0) {
                // ada baris yang gagal/di-skip
                return back()
                    ->with('warning', "{$import->imported} data berhasil diimpor.")
                    ->with('import_errors', $import->errors);
            }

            session()->flash('success', 'Data mahasiswa berhasil diimpor.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage());
        }

        return back();
    }

    public function destroy(Request $request){
        try {
            DB::beginTransaction();
        
            $mahasiswa = Mahasiswa::find($request->id_mahasiswa);
            if (!$mahasiswa) {
                return response()->json(['success' => false, 'message' => 'mahasiswa gagal ditemukan: ' . $e->getMessage()], 200);
            }
        
            // Hapus semua data yang terkait dengan mahasiswa
            Mahasiswa::where("email", $mahasiswa->email)->delete();
            Kehadiran::where("email", $mahasiswa->email)->delete();
            Dplmentoring::where("email_mahasiswa", $mahasiswa->email)->delete();
            Logkegiatan::where("email", $mahasiswa->email)->delete();
            Logbulanan::where("email", $mahasiswa->email)->delete();
            User::where("email", $mahasiswa->email)->delete();
        
            // Hapus mahasiswa
            $mahasiswa->delete();
        
            DB::commit();
        
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
        
            return response()->json(['success' => false, 'message' => 'Data gagal dihapus: ' . $e->getMessage()], 200);
        }

    }
      
}
