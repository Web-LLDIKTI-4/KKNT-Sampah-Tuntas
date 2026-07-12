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
        return view('admin.mahasiswa.index');
    }
    public function listdata()
    {
        return view('admin.mahasiswa.listdata');
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
                ->addColumn('action', function($row){
                    $csrf = csrf_field();
                    $methodField = method_field('PUT');
            
                    $actionBtn = '<div class="d-felx">'.
                                     '<form method="POST" action="'.url('mahasiswa/destroy').'" id="hapusmhs-'.$row->id_mahasiswa.'">
                                     <input type="hidden" name="id_mahasiswa" value="'.$row->id_mahasiswa.'">'.
                                         $csrf.
                                         $methodField.
                                         '<button type="submit" id="btnSubmit_hapusmhs-'.$row->id_mahasiswa.'" class="btn p-0 m-0"><small>Hapus<small></button>'.
                                     '</form>'.
                                 '</div>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
            }
    }
    public function import(){
        return view('admin.mahasiswa.import');
    }
    public function prosesimport(Request $request){
        try {
            Excel::import(new ImportMahasiswa, $request->file);
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
