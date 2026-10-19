<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use DataTables;
use App\Models\Logkegiatan;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
class AdmlogharianController extends Controller
{    
    public function index()
    {  
        return view('logharian.index');
    }
    public function listdata()
    {
        return view('logharian.listdata');
    }
    public function permhs(Request $request)
    {
        $email = $request->email;
        return view('logharian.permhs',compact('email'));
    }
    public function permhsserver(Request $request)
    {
        if ($request->ajax()) { 
            // Menemukan semua mahasiswa dengan kodept yang sesuai
            $data = Logkegiatan::where("email", $request->email)->whereNotNull('deskripsi')->get();
            return DataTables::of($data)
            ->addIndexColumn()
            ->make(true);
        }
    }
    // Link lama → export log harian lengkap (ber-scope & throttle)
    public function export(Request $request)
    {
        return redirect()->route('logharian.export', $request->only('bulan'));
    }
}