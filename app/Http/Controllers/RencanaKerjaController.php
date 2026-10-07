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

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Pt\RencanaKerjaRequest;
use App\Models\RencanaKerja;
use App\Models\User;
use App\Support\ActionButtons;
use App\Support\FileSize;

class RencanaKerjaController extends Controller
{
    use RespondsWithJson;

    private const METADATA_FIELDS = ['judul', 'tahun', 'keterangan'];

    public function index(Request $request)
    {
        Gate::authorize('viewAny', RencanaKerja::class);

        return view('rencanakerja.index', [
            'canCreate' => $request->user()->can('create', RencanaKerja::class),
        ]);
    }

    public function listdata(Request $request)
    {
        Gate::authorize('viewAny', RencanaKerja::class);

        return view('rencanakerja.listdata', [
            'showPt' => $request->user()->role !== 'pt',
        ]);
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);
        Gate::authorize('viewAny', RencanaKerja::class);

        $user = $request->user();
        $query = RencanaKerja::visibleTo($user)
            ->with(['pt:npsn,nm_lemb', 'uploader:id,name'])
            ->select('rencana_kerja.*');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('pt', fn (RencanaKerja $row) => e($row->pt?->nm_lemb ?? $row->kodept))
            ->editColumn('judul', fn (RencanaKerja $row) => $this->renderJudulCell($row))
            ->addColumn('file', fn (RencanaKerja $row) => $this->renderFileCell($row))
            ->editColumn('created_at', fn (RencanaKerja $row) => $this->renderUploadedCell($row))
            ->addColumn('action', fn (RencanaKerja $row) => $this->renderActionCell($user, $row))
            ->rawColumns(['judul', 'file', 'created_at', 'action'])
            // Jangan kirim path internal storage & data mentah lain ke browser
            ->only(['DT_RowIndex', 'pt', 'judul', 'tahun', 'file', 'created_at', 'action'])
            ->make(true);
    }

    public function tambah()
    {
        Gate::authorize('create', RencanaKerja::class);

        return view('rencanakerja.tambah', ['maxUploadBytes' => $this->getMaxUploadBytes()]);
    }

    public function insert(RencanaKerjaRequest $request)
    {
        $user = $request->user();
        $rencanaKerja = new RencanaKerja($request->safe()->only(self::METADATA_FIELDS) + $this->storeUploadedFile($request->file('file')));
        // Pemilik & pengunggah diisi server, bukan dari input
        $rencanaKerja->kodept = $user->email;
        $rencanaKerja->uploaded_by = $user->id;

        try {
            $rencanaKerja->save();
        } catch (\Throwable $exception) {
            Storage::disk(RencanaKerja::DISK)->delete($rencanaKerja->file_path);
            throw $exception;
        }

        return $this->saved();
    }

    public function edit(Request $request, string $id_rencana_kerja)
    {
        $rencanaKerja = RencanaKerja::visibleTo($request->user())->findOrFail($id_rencana_kerja);
        Gate::authorize('update', $rencanaKerja);

        return view('rencanakerja.edit', [
            'rencanaKerja' => $rencanaKerja,
            'maxUploadBytes' => $this->getMaxUploadBytes(),
        ]);
    }

    public function update(RencanaKerjaRequest $request)
    {
        $rencanaKerja = $this->findVisibleByInput($request->user(), $request->validated('id_rencana_kerja'));
        if (! $rencanaKerja) {
            return $this->notFound();
        }
        if ($denied = $this->denyUnless('update', $rencanaKerja)) {
            return $denied;
        }

        $changes = $request->safe()->only(self::METADATA_FIELDS);

        if (! $request->hasFile('file')) {
            $rencanaKerja->update($changes);

            return $this->saved();
        }

        // File lama baru dihapus setelah DB berhasil diperbarui
        $oldFilePath = $rencanaKerja->file_path;
        $newFileAttributes = $this->storeUploadedFile($request->file('file'));

        try {
            $rencanaKerja->update($changes + $newFileAttributes);
        } catch (\Throwable $exception) {
            Storage::disk(RencanaKerja::DISK)->delete($newFileAttributes['file_path']);
            throw $exception;
        }

        Storage::disk(RencanaKerja::DISK)->delete($oldFilePath);

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $rencanaKerja = $this->findVisibleByInput($request->user(), $request->input('id_rencana_kerja'));
        if (! $rencanaKerja) {
            return $this->notFound();
        }
        if ($denied = $this->denyUnless('delete', $rencanaKerja)) {
            return $denied;
        }

        $rencanaKerja->delete();

        return $this->deleted();
    }

    public function download(Request $request, string $id_rencana_kerja): StreamedResponse
    {
        $rencanaKerja = RencanaKerja::visibleTo($request->user())->findOrFail($id_rencana_kerja);
        Gate::authorize('view', $rencanaKerja);
        abort_unless(Storage::disk(RencanaKerja::DISK)->exists($rencanaKerja->file_path), 404, 'File rencana kerja tidak ditemukan.');

        return Storage::disk(RencanaKerja::DISK)->download($rencanaKerja->file_path, $rencanaKerja->nama_file);
    }

    private function denyUnless(string $ability, RencanaKerja $rencanaKerja): ?JsonResponse
    {
        return Gate::denies($ability, $rencanaKerja)
            ? $this->deleteRejected('Anda tidak memiliki akses untuk data ini.', 403)
            : null;
    }

    // Scope visibleTo: record PT lain dianggap tidak ada (404), bukan 403
    private function findVisibleByInput(User $user, mixed $id): ?RencanaKerja
    {
        if (! is_string($id) || ! Str::isUuid($id)) {
            return null;
        }

        return RencanaKerja::visibleTo($user)->find($id);
    }

    private function storeUploadedFile(UploadedFile $uploadedFile): array
    {
        return [
            'file_path' => $uploadedFile->store('rencana-kerja', RencanaKerja::DISK),
            'nama_file' => $this->sanitizeClientFileName($uploadedFile),
            'ukuran' => $uploadedFile->getSize(),
            'mime' => Str::limit((string) $uploadedFile->getMimeType(), 100, ''),
        ];
    }

    // Karakter kontrol (CR/LF) dibuang karena nama dipakai di header download
    private function sanitizeClientFileName(UploadedFile $uploadedFile): string
    {
        $cleanName = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', basename($uploadedFile->getClientOriginalName())) ?? '');

        if ($cleanName === '') {
            $cleanName = 'rencana-kerja.'.$uploadedFile->extension();
        }

        return Str::limit($cleanName, 250, '');
    }

    // Batas efektif = yang terkecil antara aturan aplikasi dan batas php.ini server
    private function getMaxUploadBytes(): int
    {
        return (int) min(RencanaKerjaRequest::MAX_KILOBYTES * 1024, UploadedFile::getMaxFilesize());
    }

    private function renderJudulCell(RencanaKerja $row): string
    {
        $keterangan = $row->keterangan
            ? '<small class="d-block text-body-secondary">'.e(Str::limit($row->keterangan, 120)).'</small>'
            : '';

        return '<div class="text-wrap"><span class="fw-medium">'.e($row->judul).'</span>'.$keterangan.'</div>';
    }

    private function renderFileCell(RencanaKerja $row): string
    {
        return '<a href="'.e(route('rencanakerja.download', $row->id_rencana_kerja)).'" class="d-inline-flex align-items-center gap-1 text-wrap">'
            .'<i class="ri-download-2-line"></i>'.e($row->nama_file).'</a>'
            .'<small class="d-block text-body-secondary">'.e(FileSize::format($row->ukuran)).'</small>';
    }

    private function renderUploadedCell(RencanaKerja $row): string
    {
        return e($row->created_at?->format('d-m-Y') ?? '-')
            .'<small class="d-block text-body-secondary">'.e($row->uploader?->name ?? '-').'</small>';
    }

    private function renderActionCell(User $user, RencanaKerja $row): string
    {
        $canUpdate = $user->can('update', $row);
        $canDelete = $user->can('delete', $row);

        if (! $canUpdate && ! $canDelete) {
            return '';
        }

        return ActionButtons::make(
            urlEdit: $canUpdate ? route('rencanakerja.edit', $row->id_rencana_kerja) : null,
            urlDelete: $canDelete ? route('rencanakerja.destroy') : null,
            idField: 'id_rencana_kerja',
            idValue: $row->id_rencana_kerja,
        );
    }
}
