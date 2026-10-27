<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Pt\RealisasiKerjaRequest;
use App\Models\RealisasiKerja;
use App\Models\User;
use App\Support\ActionButtons;
use App\Support\FileSize;

class RealisasiKerjaController extends Controller
{
    use HandlesDataTablesOrder, RespondsWithJson;

    private const METADATA_FIELDS = ['judul', 'keterangan'];

    public function index(Request $request)
    {
        Gate::authorize('viewAny', RealisasiKerja::class);

        return view('realisasikerja.index', [
            'canCreate' => $request->user()->can('create', RealisasiKerja::class),
        ]);
    }

    public function listdata(Request $request)
    {
        Gate::authorize('viewAny', RealisasiKerja::class);

        return view('realisasikerja.listdata', [
            'showPt' => in_array($request->user()->role, RealisasiKerja::ALL_PT_ROLES, true),
        ]);
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        Gate::authorize('viewAny', RealisasiKerja::class);
        $this->sanitizeDataTablesInput();

        $user = $request->user();
        $query = RealisasiKerja::visibleTo($user)
            ->with(['pt:npsn,nm_lemb', 'uploader:id,name'])
            ->select('realisasi_kerja.*');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('pt', fn (RealisasiKerja $row) => e($row->pt?->nm_lemb ?? $row->kodept))
            ->editColumn('judul', fn (RealisasiKerja $row) => $this->renderJudulCell($row))
            ->addColumn('file', fn (RealisasiKerja $row) => $this->renderFileCell($row))
            ->editColumn('created_at', fn (RealisasiKerja $row) => $this->renderUploadedCell($row))
            ->addColumn('action', fn (RealisasiKerja $row) => $this->renderActionCell($user, $row))
            // Hanya kolom tampil yang boleh dicari/diurutkan (cegah blind search ke uploader.*, file_path)
            ->whitelist(['pt.nm_lemb', 'judul', 'nama_file', 'created_at'])
            ->rawColumns(['judul', 'file', 'created_at', 'action'])
            // Jangan kirim path internal storage & data mentah lain ke browser
            ->only(['DT_RowIndex', 'pt', 'judul', 'file', 'created_at', 'action'])
            ->make(true);
    }

    public function tambah()
    {
        Gate::authorize('create', RealisasiKerja::class);

        return view('realisasikerja.tambah', ['maxUploadBytes' => $this->getMaxUploadBytes()]);
    }

    public function insert(RealisasiKerjaRequest $request)
    {
        $user = $request->user();
        $realisasiKerja = new RealisasiKerja($request->safe()->only(self::METADATA_FIELDS) + $this->storeUploadedFile($request->file('file')));
        // Pemilik & pengunggah diisi server, bukan dari input
        $realisasiKerja->kodept = $user->email;
        $realisasiKerja->uploaded_by = $user->id;

        try {
            $realisasiKerja->save();
        } catch (\Throwable $exception) {
            Storage::disk(RealisasiKerja::DISK)->delete($realisasiKerja->file_path);
            throw $exception;
        }

        return $this->saved();
    }

    public function edit(Request $request, string $id_realisasi_kerja)
    {
        $realisasiKerja = RealisasiKerja::visibleTo($request->user())->findOrFail($id_realisasi_kerja);
        Gate::authorize('update', $realisasiKerja);

        return view('realisasikerja.edit', [
            'realisasiKerja' => $realisasiKerja,
            'maxUploadBytes' => $this->getMaxUploadBytes(),
        ]);
    }

    public function update(RealisasiKerjaRequest $request)
    {
        $realisasiKerja = $this->findVisibleByInput($request->user(), $request->validated('id_realisasi_kerja'));
        if (! $realisasiKerja) {
            return $this->notFound();
        }
        if ($denied = $this->denyUnless('update', $realisasiKerja)) {
            return $denied;
        }

        $changes = $request->safe()->only(self::METADATA_FIELDS);

        if (! $request->hasFile('file')) {
            $realisasiKerja->update($changes);

            return $this->saved();
        }

        // File lama baru dihapus setelah DB berhasil diperbarui
        $oldFilePath = $realisasiKerja->file_path;
        $newFileAttributes = $this->storeUploadedFile($request->file('file'));

        try {
            $realisasiKerja->update($changes + $newFileAttributes);
        } catch (\Throwable $exception) {
            Storage::disk(RealisasiKerja::DISK)->delete($newFileAttributes['file_path']);
            throw $exception;
        }

        Storage::disk(RealisasiKerja::DISK)->delete($oldFilePath);

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $realisasiKerja = $this->findVisibleByInput($request->user(), $request->input('id_realisasi_kerja'));
        if (! $realisasiKerja) {
            return $this->notFound();
        }
        if ($denied = $this->denyUnless('delete', $realisasiKerja)) {
            return $denied;
        }

        $realisasiKerja->delete();

        return $this->deleted();
    }

    // Tampilkan PDF inline di browser (tanpa download)
    public function view(Request $request, string $id_realisasi_kerja): StreamedResponse
    {
        $realisasiKerja = RealisasiKerja::visibleTo($request->user())->findOrFail($id_realisasi_kerja);
        Gate::authorize('view', $realisasiKerja);
        abort_unless($realisasiKerja->mime === 'application/pdf', 404, 'Dokumen realisasi kerja bukan PDF.');
        abort_unless(Storage::disk(RealisasiKerja::DISK)->exists($realisasiKerja->file_path), 404, 'File realisasi kerja tidak ditemukan.');

        return Storage::disk(RealisasiKerja::DISK)->response($realisasiKerja->file_path, $realisasiKerja->nama_file, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    private function denyUnless(string $ability, RealisasiKerja $realisasiKerja): ?JsonResponse
    {
        return Gate::denies($ability, $realisasiKerja)
            ? $this->deleteRejected('Anda tidak memiliki akses untuk data ini.', 403)
            : null;
    }

    // Scope visibleTo: record PT lain dianggap tidak ada (404), bukan 403
    private function findVisibleByInput(User $user, mixed $id): ?RealisasiKerja
    {
        if (! is_string($id) || ! Str::isUuid($id)) {
            return null;
        }

        return RealisasiKerja::visibleTo($user)->find($id);
    }

    private function storeUploadedFile(UploadedFile $uploadedFile): array
    {
        return [
            'file_path' => $uploadedFile->store('realisasi-kerja', RealisasiKerja::DISK),
            'nama_file' => $this->sanitizeClientFileName($uploadedFile),
            'ukuran' => $uploadedFile->getSize(),
            'mime' => Str::limit((string) $uploadedFile->getMimeType(), 100, ''),
        ];
    }

    // Karakter kontrol (CR/LF) dibuang karena nama dipakai di header Content-Disposition
    private function sanitizeClientFileName(UploadedFile $uploadedFile): string
    {
        $cleanName = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', basename($uploadedFile->getClientOriginalName())) ?? '');

        if ($cleanName === '') {
            $cleanName = 'realisasi-kerja.'.$uploadedFile->extension();
        }

        return Str::limit($cleanName, 250, '');
    }

    // Batas efektif = yang terkecil antara aturan aplikasi dan batas php.ini server
    private function getMaxUploadBytes(): int
    {
        return (int) min(RealisasiKerjaRequest::MAX_KILOBYTES * 1024, UploadedFile::getMaxFilesize());
    }

    private function renderJudulCell(RealisasiKerja $row): string
    {
        $keterangan = $row->keterangan
            ? '<small class="d-block text-body-secondary">'.e(Str::limit($row->keterangan, 120)).'</small>'
            : '';

        return '<div class="text-wrap"><span class="fw-medium">'.e($row->judul).'</span>'.$keterangan.'</div>';
    }

    private function renderFileCell(RealisasiKerja $row): string
    {
        return '<span class="d-block text-wrap">'.e($row->nama_file).'</span>'
            .'<a href="'.e(route('realisasikerja.view', $row->id_realisasi_kerja)).'" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-1">'
            .'<i class="ri-eye-line"></i>Lihat</a>'
            .'<small class="d-block text-body-secondary">'.e(FileSize::format($row->ukuran)).'</small>';
    }

    private function renderUploadedCell(RealisasiKerja $row): string
    {
        return e($row->created_at?->format('d-m-Y') ?? '-')
            .'<small class="d-block text-body-secondary">'.e($row->uploader?->name ?? '-').'</small>';
    }

    private function renderActionCell(User $user, RealisasiKerja $row): string
    {
        $canUpdate = $user->can('update', $row);
        $canDelete = $user->can('delete', $row);

        if (! $canUpdate && ! $canDelete) {
            return '';
        }

        return ActionButtons::make(
            urlEdit: $canUpdate ? route('realisasikerja.edit', $row->id_realisasi_kerja) : null,
            urlDelete: $canDelete ? route('realisasikerja.destroy') : null,
            idField: 'id_realisasi_kerja',
            idValue: $row->id_realisasi_kerja,
        );
    }
}
