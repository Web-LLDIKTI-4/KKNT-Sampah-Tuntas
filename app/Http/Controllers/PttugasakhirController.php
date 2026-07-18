<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\User;
use App\Models\Mahasiswa;

class PttugasakhirController extends Controller
{    
    public function index()
    {  
        return view('laptugasakhir.pt.index');
    }
    public function listdata()
    {
        return view('laptugasakhir.pt.listdata');
    }
    public function listdataserver(Request $request)
    {
        if ($request->ajax()) {
            $query = Mahasiswa::query()
                ->orderBy('created_at', 'desc');

            if (in_array(Auth::user()->role, ['pt'])) {
                // Query validasi by lokasi program dan kode pt
                $emailMahasiswa = User::query()
                    ->with(['mahasiswa'])
                    ->where('location_program', Auth::user()->location_program)
                    ->whereHas('mahasiswa', function ($q) {
                        $q->where('kodept', Auth::user()->pt->npsn);
                    })
                    ->where('role', 'mahasiswa')
                    ->pluck('email');
                $query->whereIn('email', $emailMahasiswa)->get();
            }
        
            return Datatables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('tugas_akhir', function($row) {
                    return $row->tugasakhir->tautan ?? '-';
                })
                
                ->rawColumns(['tugas_akhir'])
                ->make(true);
        }
    }

}