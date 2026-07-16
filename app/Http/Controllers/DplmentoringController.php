<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Dplmentoring;
use App\Models\Logbulanan;
use App\Models\Mahasiswa;
use App\Models\Freeform;
use App\Models\Nilaikonversi;
use App\Support\ActionButtons;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class DplmentoringController extends Controller
{    
    public function index()
    {  
        return view('mentoring.index');
    }
    public function listdata()
    {
        return view('mentoring.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Dplmentoring::with(['mahasiswa'])->where("email_dpl",Auth::user()->email)->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', function($row){
                    return $row->mahasiswa->nim ?? 'tidak ada';
                })
                ->addColumn('nama', function($row){
                    return $row->mahasiswa->nama ?? 'tidak ada';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? 'tidak terdata';
                })
                ->addColumn('prodi', function($row){
                    return $row->mahasiswa->prodi ?? 'tidak ada';
                })
                ->addColumn('rekapnilai', function($row){
                    // return '<a href="#modalku" class="modalButton" data-bs-toggle="modal" data-src="'.url('dplmentoring/rekapnilai/').'/'.base64_encode($row->email_mahasiswa).'" title="Rekap Nilai Log Bulanan">rekap nilai</a>';
                    return view('components.btn-modal', [
                        'url' => url('dplmentoring/rekapnilai/').'/'.base64_encode($row->email_mahasiswa),
                        'title' => 'Rekap Nilai Log Bulanan',
                        'slot' => 'Rekap Nilai',
                    ])->render();
                })
                ->addColumn('nilai_freeform', function($row){
                    if($row->mahasiswa){
                        return '<a href="'.url('dplmentoring/nilaifreeform/').'/'.$row->mahasiswa->id_mahasiswa.'" title="Nilai konversi dan Free form">lihat data</a>';
                    }else{
                        return "tidak ada - ".$row->email_mahasiswa;
                    }
                })
                ->addColumn('tugasakhir', function($row){
                    $tautan = $row->tugasakhir->tautan ?? '-';
                    return '<a href="'.$tautan.'" target="_blank">'.$tautan.'</a>';
                })
                ->addColumn('action', function($row){
                    return view('components.btn-delete', [
                        'url' => url('dplmentoring/destroy/'.$row->id_mentoring),
                        'idField' => 'hapus_mentoring',
                        'idValue' => $row->id_mentoring,
                    ])->render();
                })
                ->rawColumns(['action','rekapnilai','nilai_freeform','tugasakhir'])
                ->make(true);
        }
    }
    public function tambah(){
        $data = Mahasiswa::whereDoesntHave('dplmentoring')->get();
        return view('mentoring.tambah',compact('data'));
    }
   
    public function insert(Request $request)
    {
        if($request->createuser){
            $data = [];
            
            foreach($request->createuser as $createuser){
                
                $mahasiswa = Mahasiswa::where('email',$createuser)->first();
                if($mahasiswa){
                    //cek aapakah sudah ada di tabel mentoring
                    $cekdata = Dplmentoring::where('email_mahasiswa',$createuser)->first();
                    if(!$cekdata){
                        $data[] = [
                            'email_mahasiswa' => $createuser,
                            'email_dpl' => Auth::user()->email,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }
            //insert data baru
            Dplmentoring::insert($data);
            return response()->json(['success'=>"user berhasil dibuat"]);
        }else{
            return response()->json(['error'=>"user harus dipilih"]);
        }
    }
    
    public function destroy(Request $request, String $id_mentoring){
        if ($id_mentoring) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_mentoring;

            //cek apakah sudah di gunakan di relasi lain
            Dplmentoring::find($id_mentoring)->delete();
    
            // Beri respons berhasil
            return response()->json(['success' => 'Data berhasil dihapus'], 200);
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }
    public function rekapnilai(Request $request) {
        // Membuat array bulan dengan pasangan angka dan nama bulan
        $bulan = [
            '1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Maret',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'Agustus',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
        
        // Mendapatkan semua data Logbulanan untuk email tertentu
        $data = Logbulanan::where('email', base64_decode($request->email))->get();
        
        // Mengelompokkan data berdasarkan bulan
        $groupedData = $data->groupBy('bulan');
    
        return view('mentoring.rekapnilai', compact('groupedData', 'bulan'));
    }
    public function nilaifreeform($id_mahasiswa){
        $mahasiswa = Mahasiswa::where("id_mahasiswa",$id_mahasiswa)->first();
        if (!$mahasiswa || !$mahasiswa->user || !$mahasiswa->user->email) {
            // Redirect or handle the case where the mahasiswa or email is not available
            return redirect('dplmentoring');
        }
        //cek apakah mahasiswa tersebut mentoring
        $cekdata = Dplmentoring::where("email_dpl",Auth::user()->email)->where("email_mahasiswa",$mahasiswa->user->email)->exists();
        if(!$cekdata){
            return redirect('dplmentoring');
        }
        $data=[
            'mahasiswa'=>$mahasiswa,
            'id_mahasiswa'=>$id_mahasiswa,
        ];
        return view('mentoring.nilaifreeform',$data);
    }
    public function nilaikonversi($id_mahasiswa){
        $mahasiswa = Mahasiswa::where("id_mahasiswa",$id_mahasiswa)->first();
        $nilai = Nilaikonversi::where("id_mahasiswa",$id_mahasiswa)->get();
        $data=[
            'mahasiswa'=>$mahasiswa,
            'nilai'=>$nilai,
        ];
        return view('mentoring.nilaifreeform_nilaikonversi',$data);
    }
    public function freeform($id_mahasiswa){
        $mahasiswa = Mahasiswa::where("id_mahasiswa",$id_mahasiswa)->first();
        $nilai = Freeform::where("id_mahasiswa",$id_mahasiswa)->get();
        $data=[
            'mahasiswa'=>$mahasiswa,
            'nilai'=>$nilai,
        ];
        return view('mentoring.nilaifreeform_freeform',$data);
    }

}