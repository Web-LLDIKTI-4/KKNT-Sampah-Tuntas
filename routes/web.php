<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PerguruantinggiController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\LogkehadiranController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\KpitargetController;
use App\Http\Controllers\KpicapaianController;
use App\Http\Controllers\LapcapaiankpiController;
use App\Http\Controllers\NilaifreeformController;

use App\Http\Controllers\LogkegiatanController;
use App\Http\Controllers\AdmlogkegiatanController;
use App\Http\Controllers\AdmlogkehadiranController;
use App\Http\Controllers\AdmlogbulananController;
use App\Http\Controllers\DpllaporanController;
use App\Http\Controllers\DplmentoringController;
use App\Http\Controllers\DplkonversinilaiController;
use App\Http\Controllers\DplfreeformController;

use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\DesaController;
use App\Http\Controllers\PjdesaController;
use App\Http\Controllers\DesaprofileController;
use App\Http\Controllers\LokasiprogramController;

use App\Http\Controllers\LogbulananController;
use App\Http\Controllers\MhsprofileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PtpesertaController;
use App\Http\Controllers\AdmlaporandplController;

use App\Http\Controllers\AdmlogharianController;
use App\Http\Controllers\TugasakhirController;
use App\Http\Controllers\LaptugasakhirController;
use App\Http\Controllers\DpllaptugasakhirController;
use App\Http\Controllers\AdmstructureformController;
use App\Http\Controllers\AdmfreeformController;
use App\Http\Controllers\AdmevaluasikegiatanController;
use App\Http\Controllers\PtevaluasikegiatanController;
use App\Http\Controllers\PtmahasiswaController;
use App\Http\Controllers\PttugasakhirController;

use App\Http\Controllers\SaranController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'index'])->name('login');
    Route::put('login', [LoginController::class, 'proseslogin']);
});

Route::middleware('auth')->group(function () {
    Route::get('logout', [LoginController::class, 'logout']);
});
//Route::get('login/createuser', [LoginController::class, 'createuser']);
Route::put('saran/insert', [SaranController::class, 'insert']);

Route::get('ptpeserta', [PtpesertaController::class, 'index']);
Route::get('ptpeserta/listdata', [PtpesertaController::class, 'listdata'])->name('ptpeserta.listdata');
Route::get('ptpeserta/listdataserver', [PtpesertaController::class, 'listdataserver'])->name('ptpeserta.listdataserver');

Route::group(['middleware' => ['auth']], function() { 
    Route::get('home', [HomeController::class, 'index'])->name('home');
    Route::get('home/{lokasi}', [HomeController::class, 'index'])->name('home.lokasi');
    Route::get('setting', [SettingController::class, 'index']);
    Route::put('setting/update', [SettingController::class, 'update']);
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('user', [UserController::class, 'index']);
    Route::get('user/listdata', [UserController::class, 'listdata']);
    Route::get('user/getdatamember', [UserController::class, 'getdatamember']);
    Route::put('user/insert', [UserController::class, 'insert']);
    Route::get('user/adduser', [UserController::class, 'adduser']);
    Route::put('user/insertuser', [UserController::class, 'insertuser']);
    Route::get('user/edit/{id}', [UserController::class, 'edit']);
    Route::put('user/updateuser', [UserController::class, 'updateuser']);

    Route::get('user/adduserpt', [UserController::class, 'adduserpt']);
    Route::put('user/insertuserpt', [UserController::class, 'insertuserpt']);
    Route::get('user/edituserpt/{id}', [UserController::class, 'edituserpt']);
    Route::put('user/updateuserpt', [UserController::class, 'updateuserpt']);

    Route::get('mahasiswa', [MahasiswaController::class, 'index']);
    Route::get('mahasiswa/listdata', [MahasiswaController::class, 'listdata'])->name('mahasiswa.listdata');
    Route::get('mahasiswa/listdataserver', [MahasiswaController::class, 'listdataserver'])->name('mahasiswa.listdataserver');
    Route::get('mahasiswa/import', [MahasiswaController::class, 'import']);
    Route::put('mahasiswa/prosesimport', [MahasiswaController::class, 'prosesimport']);
    Route::put('mahasiswa/destroy', [MahasiswaController::class, 'destroy']);

    Route::get('kpi', [KpiController::class, 'index']);
    Route::get('kpi/listdata', [KpiController::class, 'listdata'])->name('kpi.listdata');
    Route::get('kpi/listdataserver', [KpiController::class, 'listdataserver'])->name('kpi.listdataserver');
    Route::get('kpi/tambah', [KpiController::class, 'tambah']);
    Route::put('kpi/insert', [KpiController::class, 'insert']);
    Route::get('kpi/edit/{id_kpi}', [KpiController::class, 'edit']);
    Route::put('kpi/update', [KpiController::class, 'update']);
    Route::put('kpi/destroy', [KpiController::class, 'destroy']);

    Route::get('kpitarget', [KpitargetController::class, 'index']);
    Route::get('kpitarget/listdata', [KpitargetController::class, 'listdata'])->name('kpitarget.listdata');
    Route::get('kpitarget/listdataserver', [KpitargetController::class, 'listdataserver'])->name('kpitarget.listdataserver');
    Route::get('kpitarget/tambah', [KpitargetController::class, 'tambah']);
    Route::put('kpitarget/insert', [KpitargetController::class, 'insert']);
    Route::get('kpitarget/edit/{id_target}', [KpitargetController::class, 'edit']);
    Route::put('kpitarget/update', [KpitargetController::class, 'update']);
    Route::put('kpitarget/destroy', [KpitargetController::class, 'destroy']);

    //semua route dalam grup ini hanya bisa diakses oleh operator
    
    Route::get('admlaporandpl', [AdmlaporandplController::class, 'index']);
    Route::get('admlaporandpl/listdata', [AdmlaporandplController::class, 'listdata'])->name('admlaporandpl.listdata');
    Route::get('admlaporandpl/listdataserver', [AdmlaporandplController::class, 'listdataserver'])->name('admlaporandpl.listdataserver');;
    Route::get('admlaporandpl/export', [AdmlaporandplController::class, 'export']);

    Route::get('admlogharian', [AdmlogharianController::class, 'index']);
    Route::get('admlogharian/listdata', [AdmlogharianController::class, 'listdata'])->name('admlogharian.listdata');
    Route::get('admlogharian/listdataserver', [AdmlogharianController::class, 'listdataserver'])->name('admlogharian.listdataserver');
    Route::get('admlogharian/permhs/{email}', [AdmlogharianController::class, 'permhs'])->name('admlogharian.permhs');
    Route::get('admlogharian/permhsserver/{email}', [AdmlogharianController::class, 'permhsserver'])->name('admlogharian.permhsserver');
    Route::get('admlogharian/export', [AdmlogharianController::class, 'export']);

    Route::get('kecamatan', [KecamatanController::class, 'index']);
    Route::get('kecamatan/listdata', [KecamatanController::class, 'listdata'])->name('kecamatan.listdata');
    Route::get('kecamatan/listdataserver', [KecamatanController::class, 'listdataserver'])->name('kecamatan.listdataserver');
    Route::get('kecamatan/tambah', [KecamatanController::class, 'tambah']);
    Route::put('kecamatan/insert', [KecamatanController::class, 'insert']);
    Route::get('kecamatan/edit/{id_kecamatan}', [KecamatanController::class, 'edit']);
    Route::put('kecamatan/update', [KecamatanController::class, 'update']);
    Route::put('kecamatan/destroy', [KecamatanController::class, 'destroy']);

    Route::get('desa', [DesaController::class, 'index']);
    Route::get('desa/listdata', [DesaController::class, 'listdata'])->name('desa.listdata');
    Route::get('desa/listdataserver', [DesaController::class, 'listdataserver'])->name('desa.listdataserver');
    Route::get('desa/tambah', [DesaController::class, 'tambah']);
    Route::put('desa/insert', [DesaController::class, 'insert']);
    Route::get('desa/edit/{id_desa}', [DesaController::class, 'edit']);
    Route::put('desa/update', [DesaController::class, 'update']);
    Route::put('desa/destroy', [DesaController::class, 'destroy']);

    Route::get('pjdesa', [PjdesaController::class, 'index']);
    Route::get('pjdesa/listdata', [PjdesaController::class, 'listdata'])->name('pjdesa.listdata');
    Route::get('pjdesa/listdataserver', [PjdesaController::class, 'listdataserver'])->name('pjdesa.listdataserver');
    Route::get('pjdesa/tambah', [PjdesaController::class, 'tambah']);
    Route::put('pjdesa/insert', [PjdesaController::class, 'insert']);
    Route::get('pjdesa/edit/{id_pjdesa}', [PjdesaController::class, 'edit']);
    Route::put('pjdesa/update', [PjdesaController::class, 'update']);
    Route::put('pjdesa/destroy', [PjdesaController::class, 'destroy']);

    Route::get('desaprofile', [DesaprofileController::class, 'index']);
    Route::get('desaprofile/listdata', [DesaprofileController::class, 'listdata'])->name('desaprofile.listdata');
    Route::get('desaprofile/listdataserver', [DesaprofileController::class, 'listdataserver'])->name('desaprofile.listdataserver');
    Route::get('desaprofile/tambah', [DesaprofileController::class, 'tambah']);
    Route::put('desaprofile/insert', [DesaprofileController::class, 'insert']);
    Route::get('desaprofile/edit/{id_profile}', [DesaprofileController::class, 'edit']);
    Route::put('desaprofile/update', [DesaprofileController::class, 'update']);
    Route::put('desaprofile/destroy', [DesaprofileController::class, 'destroy']);

    Route::get('lokasiprogram', [LokasiprogramController::class, 'index']);
    Route::get('lokasiprogram/listdata', [LokasiprogramController::class, 'listdata'])->name('lokasiprogram.listdata');
    Route::get('lokasiprogram/listdataserver', [LokasiprogramController::class, 'listdataserver'])->name('lokasiprogram.listdataserver');
    Route::get('lokasiprogram/tambah', [LokasiprogramController::class, 'tambah']);
    Route::put('lokasiprogram/insert', [LokasiprogramController::class, 'insert']);
    Route::get('lokasiprogram/edit/{id}', [LokasiprogramController::class, 'edit']);
    Route::put('lokasiprogram/update', [LokasiprogramController::class, 'update']);
    Route::put('lokasiprogram/destroy', [LokasiprogramController::class, 'destroy']);

    Route::get('laptugasakhir', [LaptugasakhirController::class, 'index']);
    Route::get('laptugasakhir/listdata', [LaptugasakhirController::class, 'listdata'])->name('laptugasakhir.listdata');
    Route::get('laptugasakhir/listdataserver', [LaptugasakhirController::class, 'listdataserver'])->name('laptugasakhir.listdataserver');
    Route::get('laptugasakhir/export', [LaptugasakhirController::class, 'export']);

    Route::get('admstructureform', [AdmstructureformController::class, 'index']);
    Route::get('admstructureform/listdata', [AdmstructureformController::class, 'listdata'])->name('admstructureform.listdata');
    Route::get('admstructureform/listdataserver', [AdmstructureformController::class, 'listdataserver'])->name('admstructureform.listdataserver');
    Route::get('admstructureform/export', [AdmstructureformController::class, 'export']);
    
    Route::get('admfreeform', [AdmfreeformController::class, 'index']);
    Route::get('admfreeform/listdata', [AdmfreeformController::class, 'listdata'])->name('admfreeform.listdata');
    Route::get('admfreeform/listdataserver', [AdmfreeformController::class, 'listdataserver'])->name('admfreeform.listdataserver');
    Route::get('admfreeform/export', [AdmfreeformController::class, 'export']);

    Route::get('admevaluasikegiatan', [AdmevaluasikegiatanController::class, 'index']);
    Route::get('admevaluasikegiatan/hasilevaluasi', [AdmevaluasikegiatanController::class, 'hasilevaluasi'])->name('admevaluasikegiatan.hasilevaluasi');
    Route::get('admevaluasikegiatan/listdataserver', [AdmevaluasikegiatanController::class, 'listdataserver'])->name('admevaluasikegiatan.listdataserver');
    Route::get('admevaluasikegiatan/tambah', [AdmevaluasikegiatanController::class, 'tambah'])->name('admevaluasikegiatan.tambah');
    Route::put('admevaluasikegiatan/insert', [AdmevaluasikegiatanController::class, 'insert']);
    Route::get('admevaluasikegiatan/pertanyaanevaluasi', [AdmevaluasikegiatanController::class, 'pertanyaanevaluasi'])->name('admevaluasikegiatan.pertanyaanevaluasi');
    Route::get('admevaluasikegiatan/pertanyaanevaluasilistdata', [AdmevaluasikegiatanController::class, 'pertanyaanevaluasilistdata'])->name('admevaluasikegiatan.pertanyaanevaluasilistdata');
    Route::get('admevaluasikegiatan/pertanyaanevaluasiserver', [AdmevaluasikegiatanController::class, 'pertanyaanevaluasiserver'])->name('admevaluasikegiatan.pertanyaanevaluasiserver');
    Route::put('admevaluasikegiatan/pertanyaanevaluasi/destroy', [AdmevaluasikegiatanController::class, 'destroy'])->name('admevaluasikegiatan.pertanyaanevaluasi.destroy');

});

Route::middleware(['auth', 'role:dpl,admin'])->group(function () {

    Route::get('profile', [ProfileController::class, 'index']);
    Route::get('profile/data', [ProfileController::class, 'data']);
    Route::get('profile/getPoto', [ProfileController::class, 'getPoto'])->name('profile.getPoto');
    Route::get('profile/uploadpoto', [ProfileController::class, 'uploadpoto'])->name('profile.uploadpoto');
    Route::put('profile/prosesuploadpoto', [ProfileController::class, 'prosesuploadpoto']);
    Route::put('profile/update', [ProfileController::class, 'update']);
    
    Route::get('perguruantinggi', [PerguruantinggiController::class, 'index']);
    Route::get('perguruantinggi/listdata', [PerguruantinggiController::class, 'listdata'])->name('perguruantinggi.listdata');
    Route::get('perguruantinggi/listdataserver', [PerguruantinggiController::class, 'listdataserver'])->name('perguruantinggi.listdataserver');;
    Route::put('perguruantinggi/getdata', [PerguruantinggiController::class, 'getdata']);
    Route::get('perguruantinggi/tambah', [PerguruantinggiController::class, 'tambah']);
    Route::put('perguruantinggi/insert', [PerguruantinggiController::class, 'insert']);

    Route::get('admlogkegiatan', [AdmlogkegiatanController::class, 'index']);
    Route::get('admlogkegiatan/listdata', [AdmlogkegiatanController::class, 'listdata'])->name('admlogkegiatan.listdata');
    Route::get('admlogkegiatan/listdataserver', [AdmlogkegiatanController::class, 'listdataserver'])->name('admlogkegiatan.listdataserver');
    Route::get('admlogkegiatan/export', [AdmlogkegiatanController::class, 'export']);
 
    Route::get('admlogbulanan', [AdmlogbulananController::class, 'index']);
    Route::get('admlogbulanan/listdata', [AdmlogbulananController::class, 'listdata'])->name('admlogbulanan.listdata');
    Route::get('admlogbulanan/listdataserver', [AdmlogbulananController::class, 'listdataserver'])->name('admlogbulanan.listdataserver');
    Route::get('admlogbulanan/export', [AdmlogbulananController::class, 'export']);
    Route::get('admlogbulanan/formpenilaian/{id}', [AdmlogbulananController::class, 'formpenilaian']);
    Route::put('admlogbulanan/updatenilai', [AdmlogbulananController::class, 'updatenilai']);
    
    Route::get('admlogkehadiran', [AdmlogkehadiranController::class, 'index']);
    Route::get('admlogkehadiran/listdata', [AdmlogkehadiranController::class, 'listdata'])->name('admlogkehadiran.listdata');
    Route::get('admlogkehadiran/listdataserver', [AdmlogkehadiranController::class, 'listdataserver'])->name('admlogkehadiran.listdataserver');

    Route::get('lapcapaiankpi', [LapcapaiankpiController::class, 'index']);
    Route::get('lapcapaiankpi/listdata', [LapcapaiankpiController::class, 'listdata'])->name('lapcapaiankpi.listdata');
    Route::get('lapcapaiankpi/listdataserver', [LapcapaiankpiController::class, 'listdataserver'])->name('lapcapaiankpi.listdataserver');
    Route::get('lapcapaiankpi/export', [LapcapaiankpiController::class, 'export']);
});

Route::middleware(['auth', 'role:dpl'])->group(function () {
    Route::get('dpllaporan', [DpllaporanController::class, 'index']);
    Route::get('dpllaporan/listdata', [DpllaporanController::class, 'listdata']);
    Route::post('dpllaporan/tambah', [DpllaporanController::class, 'tambah'])->name('dpllaporan.tambah');
    Route::put('dpllaporan/insert', [DpllaporanController::class, 'insert']);
    Route::put('dpllaporan/destroy', [DpllaporanController::class, 'destroy']);

    Route::get('dplmentoring', [DplmentoringController::class, 'index']);
    Route::get('dplmentoring/listdata', [DplmentoringController::class, 'listdata'])->name('dplmentoring.listdata');
    Route::get('dplmentoring/listdataserver', [DplmentoringController::class, 'listdataserver'])->name('dplmentoring.listdataserver');
    Route::get('dplmentoring/tambah', [DplmentoringController::class, 'tambah']);
    Route::put('dplmentoring/insert', [DplmentoringController::class, 'insert']);
    Route::put('dplmentoring/destroy/{id_mentoring}', [DplmentoringController::class, 'destroy']);
    Route::get('dplmentoring/rekapnilai/{email}', [DplmentoringController::class, 'rekapnilai']);
    Route::get('dplmentoring/nilaifreeform/{id_mahasiswa}', [DplmentoringController::class, 'nilaifreeform']);
    Route::get('dplmentoring/nilaikonversi/{id_mahasiswa}', [DplmentoringController::class, 'nilaikonversi']);
    Route::get('dplmentoring/freeform/{id_mahasiswa}', [DplmentoringController::class, 'freeform']);
    
    Route::get('dplkonversinilai', [DplkonversinilaiController::class, 'index']);
    Route::get('dplkonversinilai/listdata', [DplkonversinilaiController::class, 'listdata'])->name('dplkonversinilai.listdata');
    Route::get('dplkonversinilai/listdataserver', [DplkonversinilaiController::class, 'listdataserver'])->name('dplkonversinilai.listdataserver');
    Route::get('dplkonversinilai/tambah', [DplkonversinilaiController::class, 'tambah']);
    Route::put('dplkonversinilai/insert', [DplkonversinilaiController::class, 'insert']);
    Route::get('dplkonversinilai/edit/{id_konversi}', [DplkonversinilaiController::class, 'edit']);
    Route::put('dplkonversinilai/update', [DplkonversinilaiController::class, 'update']);
    Route::put('dplkonversinilai/destroy', [DplkonversinilaiController::class, 'destroy']);

    Route::get('dplfreeform', [DplfreeformController::class, 'index']);
    Route::get('dplfreeform/listdata', [DplfreeformController::class, 'listdata'])->name('dplfreeform.listdata');
    Route::get('dplfreeform/listdataserver', [DplfreeformController::class, 'listdataserver'])->name('dplfreeform.listdataserver');
    Route::get('dplfreeform/tambah', [DplfreeformController::class, 'tambah']);
    Route::put('dplfreeform/insert', [DplfreeformController::class, 'insert']);
    Route::get('dplfreeform/edit/{id_freeform}', [DplfreeformController::class, 'edit']);
    Route::put('dplfreeform/update', [DplfreeformController::class, 'update']);
    Route::put('dplfreeform/destroy', [DplfreeformController::class, 'destroy']);

    Route::get('dpllaptugasakhir', [DpllaptugasakhirController::class, 'index']);
    Route::get('dpllaptugasakhir/listdata', [DpllaptugasakhirController::class, 'listdata'])->name('dpllaptugasakhir.listdata');
    Route::get('dpllaptugasakhir/listdataserver', [DpllaptugasakhirController::class, 'listdataserver'])->name('dpllaptugasakhir.listdataserver');
    Route::get('dpllaptugasakhir/export', [DpllaptugasakhirController::class, 'export']);
    Route::put('dpllaptugasakhir/nilai', [DpllaptugasakhirController::class, 'nilai']);
});

Route::middleware(['auth', 'role:mahasiswa'])->group(function () {
    Route::get('logkehadiran', [LogkehadiranController::class, 'index']);
    Route::get('logkehadiran/listdata', [LogkehadiranController::class, 'listdata'])->name('logkehadiran.listdata');
    Route::get('logkehadiran/listdataserver', [LogkehadiranController::class, 'listdataserver'])->name('logkehadiran.listdataserver');
    Route::get('logkehadiran/tambah', [LogkehadiranController::class, 'tambah']);
    Route::put('logkehadiran/insert', [LogkehadiranController::class, 'insert']);
    Route::get('logkehadiran/tambahizin', [LogkehadiranController::class, 'tambahizin']);
    Route::put('logkehadiran/insertizin', [LogkehadiranController::class, 'insertizin']);
    Route::get('logkehadiran/export', [LogkehadiranController::class, 'export']);
    //semua route dalam grup ini hanya bisa diakses siswa

    Route::get('logkegiatan', [LogkegiatanController::class, 'index']);
    Route::get('logkegiatan/listdata', [LogkegiatanController::class, 'listdata'])->name('logkegiatan.listdata');
    Route::get('logkegiatan/listdataserver', [LogkegiatanController::class, 'listdataserver'])->name('logkegiatan.listdataserver');
    Route::get('logkegiatan/tambah', [LogkegiatanController::class, 'tambah']);
    Route::put('logkegiatan/insert', [LogkegiatanController::class, 'insert']);
    Route::get('logkegiatan/edit/{id_log}', [LogkegiatanController::class, 'edit']);
    Route::put('logkegiatan/update', [LogkegiatanController::class, 'update']);
    Route::put('logkegiatan/destroy', [LogkegiatanController::class, 'destroy']);
    Route::get('logkegiatan/export', [LogkegiatanController::class, 'export']);

    Route::get('kpicapaian', [KpicapaianController::class, 'index']);
    Route::get('kpicapaian/listdata', [KpicapaianController::class, 'listdata'])->name('kpicapaian.listdata');
    Route::get('kpicapaian/listdataserver', [KpicapaianController::class, 'listdataserver'])->name('kpicapaian.listdataserver');
    Route::get('kpicapaian/tambah', [KpicapaianController::class, 'tambah']);
    Route::put('kpicapaian/insert', [KpicapaianController::class, 'insert']);
    Route::get('kpicapaian/edit/{id_capaian}', [KpicapaianController::class, 'edit']);
    Route::put('kpicapaian/update', [KpicapaianController::class, 'update']);
    Route::put('kpicapaian/destroy', [KpicapaianController::class, 'destroy']);
    Route::post('kpicapaian/kpitarget', [KpicapaianController::class, 'kpitarget']);

    Route::get('logbulanan', [LogbulananController::class, 'index']);
    Route::post('logbulanan/tambah', [LogbulananController::class, 'tambah'])->name('logbulanan.tambah');
    Route::put('logbulanan/insert', [LogbulananController::class, 'insert']);
    Route::put('logbulanan/destroy', [LogbulananController::class, 'destroy']);
    Route::get('logbulanan/listdata', [LogbulananController::class, 'listdata']);
    
    Route::get('mhsprofile', [MhsprofileController::class, 'index']);
    Route::get('mhsprofile/data', [MhsprofileController::class, 'data']);
    Route::get('mhsprofile/getPoto', [MhsprofileController::class, 'getPoto'])->name('mhsprofile.getPoto');
    Route::get('mhsprofile/uploadpoto', [MhsprofileController::class, 'uploadpoto'])->name('mhsprofile.uploadpoto');
    Route::put('mhsprofile/prosesuploadpoto', [MhsprofileController::class, 'prosesuploadpoto']);
    Route::put('mhsprofile/update', [MhsprofileController::class, 'update']);
    Route::get('mhsprofile/formlokasi', [MhsprofileController::class, 'formlokasi']);
    Route::put('mhsprofile/setlokasi', [MhsprofileController::class, 'setlokasi']);

    Route::get('nilaifreeform', [NilaifreeformController::class, 'index']);
    Route::get('nilaifreeform/nilaikonversi', [NilaifreeformController::class, 'nilaikonversi']);
    Route::get('nilaifreeform/freeform', [NilaifreeformController::class, 'freeform']);
    //tugas akhir
    Route::get('tugasakhir', [TugasakhirController::class, 'index']);
    Route::get('tugasakhir/listdata', [TugasakhirController::class, 'listdata']);
    Route::get('tugasakhir/tambah', [TugasakhirController::class, 'tambah']);
    Route::put('tugasakhir/insert', [TugasakhirController::class, 'insert']);
    Route::get('tugasakhir/edit/{id_tugasakhir}', [TugasakhirController::class, 'edit']);
    Route::put('tugasakhir/update', [TugasakhirController::class, 'update']);
    Route::put('tugasakhir/destroy', [TugasakhirController::class, 'destroy']);
});
Route::middleware(['auth', 'role:pt'])->group(function () {
    Route::get('ptevaluasikegiatan', [PtevaluasikegiatanController::class, 'index']);
    Route::get('ptevaluasikegiatan/tambah', [PtevaluasikegiatanController::class, 'tambah']);
    Route::put('ptevaluasikegiatan/insert', [PtevaluasikegiatanController::class, 'insert']);

    Route::get('ptmahasiswa', [PtmahasiswaController::class, 'index']);
    Route::get('ptmahasiswa/listdata', [PtmahasiswaController::class, 'listdata'])->name('ptmahasiswa.listdata');
    Route::get('ptmahasiswa/listdataserver', [PtmahasiswaController::class, 'listdataserver'])->name('ptmahasiswa.listdataserver');
    
    Route::get('pttugasakhir', [PttugasakhirController::class, 'index']);
    Route::get('pttugaskahir/listdata', [PttugasakhirController::class, 'listdata'])->name('pttugaskahir.listdata');
    Route::get('pttugaskahir/listdataserver', [PttugasakhirController::class, 'listdataserver'])->name('pttugaskahir.listdataserver');
});