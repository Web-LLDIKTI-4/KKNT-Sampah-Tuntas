<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\LokasiProgram;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionButtons;
use Illuminate\Support\Facades\Storage;

class LokasiprogramController extends Controller
{
    public function index()
    {
        return view('lokasiprogram.index');
    }
    public function listdata()
    {
        return view('lokasiprogram.listdata');
    }
    public function listdataserver(Request $request)
    {
        if ($request->ajax()) {
            $data = LokasiProgram::get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('gambar', function($row){
                    if ($row->gambar) {
                        return '<img src="'.asset('storage/'.$row->gambar).'" alt="Gambar" width="100">';
                    } else {
                        return 'Tidak ada gambar';
                    }
                })
                ->addColumn('action', function($row){
                    return ActionButtons::editDelete(url('lokasiprogram/edit/'.$row->id), $row->id);
                })
                ->rawColumns(['action', 'gambar'])
                ->make(true);
        }
    }
    public function tambah(){
        return view('lokasiprogram.tambah');
    }

    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lokasi' => 'required',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'nama_lokasi.required' => 'Nama lokasi harus diisi.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'gambar.max' => 'Ukuran gambar maksimal 5MB.',
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = LokasiProgram::where("nama_lokasi", $request->nama_lokasi)->exists();
            if ($cekdata) {
                $validator->errors()->add('nama_lokasi', 'Data sudah ada!');
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Data gagal disimpan!', 'errors' => $validator->errors()], 200);
        }

        $data = ['nama_lokasi' => $request->nama_lokasi];

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $data['gambar'] = $file->store('lokasi', 'public');
            $data['gambar_nama_asli'] = $file->getClientOriginalName();
            $data['gambar_ukuran'] = $file->getSize();
        }

        LokasiProgram::create($data);
        return response()->json(['success' => true, 'message' => 'Data berhasil disimpan'], 200);
    }

    public function edit(Request $request){
        $lokasiprogram = LokasiProgram::where("id",$request->id)->first();
        $data=[
            'data'=>$lokasiprogram,
        ];
        return view('lokasiprogram.edit',$data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lokasi' => 'required',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'nama_lokasi.required' => 'Nama lokasi harus diisi.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'gambar.max' => 'Ukuran gambar maksimal 5MB.',
        ]);

        $validator->after(function($validator) use ($request) {
            $cekdata = LokasiProgram::where("nama_lokasi", $request->nama_lokasi)
                ->where("id", "!=", $request->id)
                ->exists();
            if ($cekdata) {
                $validator->errors()->add('nama_lokasi', 'Data sudah ada!');
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Data gagal disimpan!', 'errors' => $validator->errors()], 200);
        }

        $lokasiprogram = LokasiProgram::find($request->id);
        if (!$lokasiprogram) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan!'], 404);
        }

        // Mulai dari data yang sudah ada
        $data = [
            'nama_lokasi' => $request->nama_lokasi,
            'gambar' => $lokasiprogram->gambar,
        ];

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama dulu
            if ($lokasiprogram->gambar && Storage::disk('public')->exists($lokasiprogram->gambar)) {
                Storage::disk('public')->delete($lokasiprogram->gambar);
            }

            $file = $request->file('gambar');
            $data['gambar'] = $file->store('lokasi', 'public');
        }

        $lokasiprogram->update($data);
        return response()->json(['success' => true, 'message' => 'Data berhasil disimpan'], 200);
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
