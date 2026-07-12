<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\LokasiProgram;
use Illuminate\Support\Facades\Validator;

class LokasiprogramController extends Controller
{
    public function index()
    {
        return view('admin.lokasiprogram.index');
    }
    public function listdata()
    {
        return view('admin.lokasiprogram.listdata');
    }
    public function listdataserver(Request $request)
    {
        if ($request->ajax()) {
            $data = LokasiProgram::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $actionBtn = '<div class="d-felx"><a href="#modalku" data-bs-toggle="modal" class="modalButton p-0 m-0" data-src="'.url('lokasiprogram/edit/'.$row->id).'" title="Edit Data"><i class="ri-edit-box-line text-success"></i></a> <a href="javascript:void(0)" id="hapus_'.$row->id.'"  class="p-0 m-0"><i class="ri-delete-bin-3-line text-danger"></a></div>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function tambah(){
        return view('admin.lokasiprogram.tambah');
    }
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lokasi' => 'required',
        ], [
            'nama_lokasi.required' => 'Nama lokasi harus diisi.',
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = LokasiProgram::where("nama_lokasi",$request->nama_lokasi)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('nama_lokasi', 'Data sudah ada!');
            }
        });

        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Data gagal disimpan!','errors' => $validator->errors()], 200);
        }
        $data=[
            'nama_lokasi'=>$request->nama_lokasi,
        ];
        LokasiProgram::create($data);
        return response()->json(['success'=>true,'message' => 'Data berhasil disimpan'], 200);
    }
    public function edit(Request $request){
        $lokasiprogram = LokasiProgram::where("id",$request->id)->first();
        $data=[
            'data'=>$lokasiprogram,
        ];
        return view('admin.lokasiprogram.edit',$data);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lokasi' => 'required',
        ], [
            'nama_lokasi.required' => 'Nama lokasi harus diisi.',
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = LokasiProgram::where("nama_lokasi",$request->nama_lokasi)
            ->where("id","!=",$request->id)
            ->exists();
            if ($cekdata) {
                $validator->errors()->add('nama_lokasi', 'Data sudah ada!');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan!',
                'errors' => $validator->errors()
            ], 200);
        }

        $lokasiprogram = LokasiProgram::find($request->id);
        if (!$lokasiprogram) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404);
        }

        $data=[
            'nama_lokasi'=>$request->nama_lokasi,
        ];
        $lokasiprogram->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan'
        ], 200);
    }
    public function destroy(Request $request){
        if ($request->has('id')) {
            $id = $request->id;
            LokasiProgram::find($id)->delete();
            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        } else {
            return response()->json(['error' => 'Tidak ada ID yang diterima'], 400);
        }
    }
}
