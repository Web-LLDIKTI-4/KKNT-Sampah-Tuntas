<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Kpi;
use App\Models\Dplmentoring;
use App\Models\Nilaikonversi;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionButtons;

class DplkonversinilaiController extends Controller
{    
    public function index()
    {  
        return view('konversinilai.index');
    }
    public function listdata()
    {
        return view('konversinilai.listdata');
    }
    public function listdataserver(Request $request)
    {

        if ($request->ajax()) {
            $data = Nilaikonversi::with(['mahasiswa'])->where("email_dpl",Auth::user()->email)->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('nim', function($row){
                    return $row->mahasiswa->nim ?? 'NIM Tidak Tersedia';
                })
                ->addColumn('nama', function($row){
                    return $row->mahasiswa->nama ?? 'Nama Tidak Tersedia';
                })
                ->addColumn('nm_lemb', function($row){
                    return $row->mahasiswa->sp->nm_lemb ?? 'Nama Lembaga Tidak Tersedia';
                })
                ->addColumn('prodi', function($row){
                    return $row->mahasiswa->prodi ?? 'Prodi Tidak Tersedia';
                })
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(
                        url('dplkonversinilai/edit/'.$row->id_konversi),
                        $row->id_konversi,
                        'btn-action-edit modalButton',
                        'ri-edit-box-line',
                        'Edit Nilai'
                    );
                })
                ->rawColumns(['action','rekapnilai'])
                ->make(true);
        }
    }
    public function tambah(){
        $mahasiswa = Dplmentoring::with(['mahasiswa'])->where("email_dpl",Auth::user()->email)->get();
        $data=[
            'mahasiswa'=>$mahasiswa
        ];
        return view('konversinilai.tambah',$data);
    }
   
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matakuliah' => 'required',
            'sks' => 'required|numeric',
            'nilai_dpl' => 'required|numeric',
        ], [
            'matakuliah.required' => 'Matakuliah harus di isi.',
            'sks.required' => 'SKS harus di isi.',
            'sks.numeric' => 'SKS harus angka.',
            'nilai_dpl.required' => 'Nilai DPL harus di isi.',
            'nilai_dpl.numeric' => 'Nilai DPL harus angka.',
        ]);
        // Kondisi untuk memvalidasi 'nilai_dpa' jika ada kiriman
        $validator->sometimes('nilai_dpa', 'numeric', function ($request) {
            return !is_null($request->nilai_dpa);
        });
        // Tambahkan pesan error kustom untuk 'nilai_dpa.numeric'
        $validator->setCustomMessages([
            'nilai_dpa.numeric' => 'Nilai DPA, isian harus angka.'
        ]);
        
        $validator->after(function ($validator) use ($request) {
            $cekdata = Nilaikonversi::where("id_mahasiswa",$request->id_mahasiswa)->where("matakuliah", $request->matakuliah)->exists(); // Menggunakan exists() untuk mengecek keberadaan data
            if ($cekdata) {
                $validator->errors()->add('matakuliah', 'Matakuliah sudah di datakan');
            }
        });
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200);
        }
        
        $data = [
            'id_mahasiswa' => $request->id_mahasiswa,            
            'matakuliah' => $request->matakuliah,
            'sks' => $request->sks,
            'nilai_dpl' => $request->nilai_dpl,
            'nilai_dpa' => $request->nilai_dpa,
            'email_dpl' => Auth::user()->email,
        ];
        Nilaikonversi::insert($data);
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan!'
        ], 200);
    }
    
    public function destroy(Request $request){
        if ($request->has('id_konversi')) {
            // Lakukan tindakan penghapusan di sini
            $id = $request->id_konversi;
            //cek apakah sudah di gunakan di relasi lain
            
            Nilaikonversi::find($id)->delete();
    
            // Beri respons berhasil
            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        } else {
            // Jika tidak ada id yang diterima, kembalikan pesan kesalahan
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }
    public function edit(Request $request){
        $datanilai = Nilaikonversi::find($request->id_konversi);
        $data=[
            'data'=>$datanilai
        ];
        return view('konversinilai.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matakuliah' => 'required',
            'sks' => 'required|numeric',
            'nilai_dpl' => 'required|numeric',
        ], [
            'matakuliah.required' => 'Matakuliah harus di isi.',
            'sks.required' => 'SKS harus di isi.',
            'sks.numeric' => 'SKS harus angka.',
            'nilai_dpl.required' => 'Nilai DPL harus di isi.',
            'nilai_dpl.numeric' => 'Nilai DPL harus angka.',
        ]);

        // Kondisi untuk memvalidasi 'nilai_dpa' jika ada kiriman
        $validator->sometimes('nilai_dpa', 'numeric', function ($request) {
            return !is_null($request->nilai_dpa);
        });
        // Tambahkan pesan error kustom untuk 'nilai_dpa.numeric'
        $validator->setCustomMessages([
            'nilai_dpa.numeric' => 'Nilai DPA, isian harus angka.'
        ]);
        
        
        $validator->after(function ($validator) use ($request) {
            $cekdata = Nilaikonversi::where("id_mahasiswa",$request->id_mahasiswa)
            ->where("matakuliah", $request->matakuliah)
            ->where("id_konversi","!=", $request->id_konversi)
            ->exists(); // Menggunakan exists() untuk mengecek keberadaan data
            if ($cekdata) {
                $validator->errors()->add('matakuliah', 'Matakuliah sudah terdata pada mahasiswa ini');
            }
        });
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200);
        }
        
        $data = [
            'id_mahasiswa' => $request->id_mahasiswa,            
            'matakuliah' => $request->matakuliah,
            'sks' => $request->sks,
            'nilai_dpl' => $request->nilai_dpl,
            'nilai_dpa' => $request->nilai_dpa,
            'email_dpl' => Auth::user()->email,
        ];
        Nilaikonversi::where("id_konversi",$request->id_konversi)->where("id_mahasiswa",$request->id_mahasiswa)->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan!'
        ], 200);
    }
}