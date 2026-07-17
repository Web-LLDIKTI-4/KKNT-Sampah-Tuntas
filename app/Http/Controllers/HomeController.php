<?php  
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\Dpllaporan;
use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Kpicapaian;
use App\Models\Evaluasikegiatan;
use App\Models\Saran;
use App\Models\Kehadiran;

use DB;
class HomeController extends Controller
{    
    public function index($lokasi = null)
    {  
        ini_set('memory_limit', '1024M');

        // dpl dan mahasiswa wajib login dengan lokasi program terpilih.
        // Jaga agar url selalu konsisten dengan lokasi yang tersimpan di session (dibandingkan dalam huruf kecil).
        if (in_array(Auth::user()->role, ['dpl', 'mahasiswa'])) {
            $lokasiSession = session('lokasi_program');
            if ($lokasiSession && mb_strtolower((string) $lokasi) !== mb_strtolower($lokasiSession)) {
                return redirect(url('home/'.rawurlencode(mb_strtolower($lokasiSession))));
            }
        }

        $jumlahdpl = User::where("role","dpl")->count();
        $jumlahpt = Mahasiswa::select('kodept')->groupBy('kodept')->get()->count();
        $saran = Saran::get();
        if(Auth::user()->role == "mahasiswa"){
            $email = Auth::user()->email;
            $jumlahcapaiankpi = 0;
            if(Auth::user()->akses == "pjdesa"){
                $jumlahcapaiankpi = Kpicapaian::where('email', $email)
                    ->distinct('id_kpi')
                    ->count('id_kpi');
            }

            $jumlahmahasiswa = Mahasiswa::count(); 
        
            $jumlahlogbulanan = Logbulanan::where('email', $email)
                ->select(DB::raw('count(*) as count, bulan'))
                ->groupBy('bulan')
                ->get()
                ->count();
            
            $jumlahlogkegiatan = Logkegiatan::where('email', $email)
                ->select(DB::raw('count(*) as count, tanggal'))
                ->groupBy('tanggal')
                ->get()
                ->count();
            $kehadiran = Kehadiran::where('email', $email)->where('tanggal', date('Y-m-d'))->first();
            $data = [
                'jumlahmahasiswa'=>$jumlahmahasiswa,
                'jumlahdpl'=>$jumlahdpl,
                'jumlahlogbulanan'=>$jumlahlogbulanan,
                'jumlahlogkegiatan'=>$jumlahlogkegiatan,
                'jumlahpt'=>$jumlahpt,
                'jumlahcapaiankpi' => $jumlahcapaiankpi,
                'kehadiran' => $kehadiran,
            ];
            return view('index-user',$data);
        }else if (Auth::user()->role == "pt"){
            $jumlahmahasiswa = Mahasiswa::where('kodept',Auth::user()->email)->get()->count();
            $data = [
                'jumlahmahasiswa'=>$jumlahmahasiswa,
                'jumlahdpl'=>$jumlahdpl,
                'jumlahlogbulanan'=>0,
                'jumlahlogkegiatan'=>0,
                'jumlahpt'=>$jumlahpt,
                'jumlahcapaiankpi' => 0,
            ];
            return view('index-member',$data);
        }else{
            //cek data
            $jumlahdpl=0;
            if(Auth::user()->role == "dpl"){
                $exists = Dpl::where("email", Auth::user()->email)->exists();
                if (!$exists) {
                    return redirect(url('profile'));
                }
                $jumlahlaporandpl = Dpllaporan::where('email', Auth::user()->email)
                ->select(DB::raw('count(*) as count, bulan'))
                ->groupBy('bulan')
                ->get()
                ->count();
                $jumlahdplmentoring = Dplmentoring::where('email_dpl', Auth::user()->email)->count();
                
                // Ambil email pengguna saat ini
                $emailDpl = Auth::user()->email;

                // Subquery untuk mendapatkan id_mahasiswa dari mahasiswa yang terkait dengan email_dpl saat ini
                $subquery = DB::table('mahasiswa')
                    ->select('id_mahasiswa')
                    ->whereIn('email', function($query) use ($emailDpl) {
                        $query->select('email_mahasiswa')
                            ->from('dpl_mentoring')
                            ->where('email_dpl', $emailDpl);
                    });
                
                // Query utama untuk menghitung jumlah id_mahasiswa unik dari nilai_konversi yang terkait dengan email_dpl saat ini dan id_mahasiswa dari subquery
                $jumlahdplnilaikonversi = DB::table('nilai_konversi')
                ->where('email_dpl', $emailDpl)
                ->whereIn('id_mahasiswa', $subquery)
                ->distinct('id_mahasiswa')
                ->count('id_mahasiswa');
                
                $subqueryEmailmhs = DB::table('dpl_mentoring')
                ->select('email_mahasiswa')
                ->where('email_dpl', $emailDpl);
            
                // Main query to count unique monthly logs for these students
                $jumlahlogbulanan = DB::table(DB::raw("(SELECT bulan, email FROM logkegiatan_bulanan GROUP BY bulan, email) as grouped"))
                    ->select(DB::raw('count(*) as count'))
                    ->whereIn('email', $subqueryEmailmhs)
                    ->value('count');
                
                $jumlahmahasiswa = Dplmentoring::where('email_dpl',$emailDpl)->count();

                  // Menghitung jumlah grup tanggal dan email unik
                $jumlahlogkegiatan = DB::table(DB::raw("(SELECT tanggal, email FROM logkegiatan GROUP BY tanggal, email) as grouped"))
                ->select(DB::raw('count(*) as count'))
                ->whereIn('email', $subqueryEmailmhs)
                ->value('count');

            }else{
                $jumlahlaporandpl = Dpllaporan::count();
                $jumlahlogbulanan = DB::table(DB::raw("(SELECT bulan, email FROM logkegiatan_bulanan GROUP BY bulan, email) as grouped"))
                ->select(DB::raw('count(*) as count'))
                ->value('count');
                $jumlahmahasiswa = Mahasiswa::count();
                  // Menghitung jumlah grup tanggal dan email unik
                $jumlahlogkegiatan = DB::table(DB::raw("(SELECT tanggal, email FROM logkegiatan GROUP BY tanggal, email) as grouped"))
                ->select(DB::raw('count(*) as count'))
                ->value('count');
                $jumlahdpl = User::where("role","dpl")->count();
                $jumlahdplmentoring = Dplmentoring::count();
                // Subquery untuk mendapatkan id_mahasiswa dari mahasiswa yang terkait dengan email_dpl saat ini
                $subquery = DB::table('mahasiswa')
                    ->select('id_mahasiswa')
                    ->whereIn('email', function($query) {
                        $query->select('email_mahasiswa')
                            ->from('dpl_mentoring');
                    });

                // Main query to count unique id_mahasiswa in nilai_konversi related to the current email_dpl and id_mahasiswa from subquery
                $jumlahdplnilaikonversi = DB::table('nilai_konversi')
                    ->whereIn('id_mahasiswa', $subquery)
                    ->distinct('id_mahasiswa')
                    ->count('id_mahasiswa');
            
            }            
            $data = [
                'jumlahmahasiswa'=>$jumlahmahasiswa,
                'jumlahdpl'=>$jumlahdpl,
                'jumlahlogbulanan'=>$jumlahlogbulanan,
                'jumlahlogkegiatan'=>$jumlahlogkegiatan,
                'jumlahpt'=>$jumlahpt,
                'jumlahlaporandpl'=>$jumlahlaporandpl,
                'jumlahdplmentoring'=>$jumlahdplmentoring,
                'jumlahdplnilaikonversi'=>$jumlahdplnilaikonversi,
                'saran'=>$saran,
            ];
            return view('index-admin',$data);
        }
    }  
}
