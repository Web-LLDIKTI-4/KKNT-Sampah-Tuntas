<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Requests\Admin\PanduanRequest;
use App\Models\Panduan;
use App\Support\ActionButtons;
use App\Support\FileSize;

class PanduanController extends Controller
{
    use RespondsWithJson;

    private const METADATA_FIELDS = ['judul', 'deskripsi', 'is_aktif'];

    public function index()
    {
        return view('panduan.index');
    }

    public function listdata()
    {
        return view('panduan.listdata');
    }

    public function listdataserver(Request $request)
    {
        abort_unless($request->ajax(), 404);

        return DataTables::of(Panduan::with('uploader:id,name'))
            ->addIndexColumn()
            ->editColumn('judul', fn (Panduan $row) => $this->renderJudulCell($row))
            ->editColumn('is_aktif', fn (Panduan $row) => $row->is_aktif
                ? '<span class="badge bg-label-success">Aktif</span>'
                : '<span class="badge bg-label-secondary">Nonaktif</span>')
            ->addColumn('file', fn (Panduan $row) => $this->renderFileCell($row))
            ->editColumn('created_at', fn (Panduan $row) => $this->renderUploadedCell($row))
            ->addColumn('action', fn (Panduan $row) => ActionButtons::make(
                urlEdit: url('panduan/edit/'.$row->id_panduan),
                urlDelete: url('panduan/destroy'),
                idField: 'id_panduan',
                idValue: $row->id_panduan,
            ))
            ->rawColumns(['judul', 'is_aktif', 'file', 'created_at', 'action'])
            // Jangan kirim path internal storage & data mentah lain ke browser
            ->only(['DT_RowIndex', 'judul', 'is_aktif', 'file', 'created_at', 'action'])
            ->make(true);
    }

    public function tambah()
    {
        return view('panduan.tambah', ['maxUploadBytes' => $this->getMaxUploadBytes()]);
    }

    public function insert(PanduanRequest $request)
    {
        $metadata = $request->safe()->only(self::METADATA_FIELDS);

        Panduan::create($metadata + $this->storeUploadedFile($request->file('file')) + [
            'uploaded_by' => $request->user()->id,
        ]);

        return $this->saved();
    }

    public function edit(string $id_panduan)
    {
        return view('panduan.edit', [
            'panduan' => Panduan::findOrFail($id_panduan),
            'maxUploadBytes' => $this->getMaxUploadBytes(),
        ]);
    }

    public function update(PanduanRequest $request)
    {
        $panduan = Panduan::findOrFail($request->validated('id_panduan'));
        $changes = $request->safe()->only(self::METADATA_FIELDS);

        if (! $request->hasFile('file')) {
            $panduan->update($changes);

            return $this->saved();
        }

        // File lama baru dihapus setelah DB berhasil diperbarui agar panduan tidak kehilangan file
        $oldFilePath = $panduan->file_path;
        $newFileAttributes = $this->storeUploadedFile($request->file('file'));

        try {
            $panduan->update($changes + $newFileAttributes);
        } catch (\Throwable $exception) {
            Storage::disk(Panduan::DISK)->delete($newFileAttributes['file_path']);
            throw $exception;
        }

        Storage::disk(Panduan::DISK)->delete($oldFilePath);

        return $this->saved();
    }

    public function destroy(Request $request)
    {
        $panduan = $this->findPanduanByInput($request->input('id_panduan'));
        if (! $panduan) {
            return $this->notFound();
        }

        $panduan->delete();

        return $this->deleted();
    }

    public function download(string $id_panduan): StreamedResponse
    {
        $panduan = Panduan::findOrFail($id_panduan);
        abort_unless(Storage::disk(Panduan::DISK)->exists($panduan->file_path), 404, 'File panduan tidak ditemukan.');

        return Storage::disk(Panduan::DISK)->download($panduan->file_path, $panduan->nama_file);
    }

    private function storeUploadedFile(UploadedFile $uploadedFile): array
    {
        return [
            'file_path' => $uploadedFile->store('panduan', Panduan::DISK),
            'nama_file' => $this->sanitizeClientFileName($uploadedFile),
            'ukuran' => $uploadedFile->getSize(),
            'mime' => Str::limit((string) $uploadedFile->getMimeType(), 100, ''),
        ];
    }

    // Input array membuat find() mengembalikan Collection (truthy) dan delete() jadi 500
    private function findPanduanByInput(mixed $panduanId): ?Panduan
    {
        if (! is_string($panduanId) || ! Str::isUuid($panduanId)) {
            return null;
        }

        return Panduan::find($panduanId);
    }

    // Nama asli dipakai di header download & tampilan; karakter kontrol (CR/LF) dibuang agar data bersih
    private function sanitizeClientFileName(UploadedFile $uploadedFile): string
    {
        $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', basename($uploadedFile->getClientOriginalName())) ?? '';
        $cleanName = trim($cleanName);

        if ($cleanName === '') {
            $cleanName = 'panduan.'.$uploadedFile->extension();
        }

        return Str::limit($cleanName, 250, '');
    }

    // Batas efektif = yang terkecil antara aturan aplikasi dan batas php.ini server
    private function getMaxUploadBytes(): int
    {
        return (int) min(PanduanRequest::MAX_KILOBYTES * 1024, UploadedFile::getMaxFilesize());
    }

    private function renderJudulCell(Panduan $row): string
    {
        $description = $row->deskripsi
            ? '<small class="d-block text-body-secondary">'.e(Str::limit($row->deskripsi, 120)).'</small>'
            : '';

        return '<div class="text-wrap"><span class="fw-medium">'.e($row->judul).'</span>'.$description.'</div>';
    }

    private function renderFileCell(Panduan $row): string
    {
        return '<a href="'.e(route('panduan.download', $row->id_panduan)).'" class="d-inline-flex align-items-center gap-1 text-wrap">'
            .'<i class="ri-download-2-line"></i>'.e($row->nama_file).'</a>'
            .'<small class="d-block text-body-secondary">'.e(FileSize::format($row->ukuran)).'</small>';
    }

    private function renderUploadedCell(Panduan $row): string
    {
        return e($row->created_at?->format('d-m-Y') ?? '-')
            .'<small class="d-block text-body-secondary">'.e($row->uploader?->name ?? '-').'</small>';
    }
}
