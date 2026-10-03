@php
    $panduanList ??= collect();
    $totalPanduan = $panduanList->count();
@endphp
<div class="panduan-toolbar">
    <div class="input-group lokasi-search panduan-search">
        <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
        <input type="search" id="panduanSearch" class="form-control" placeholder="Cari judul atau isi panduan..."
            aria-label="Cari panduan" autocomplete="off" @disabled($totalPanduan === 0) />
    </div>
    <span class="panduan-count" id="panduanCount" data-total="{{ $totalPanduan }}" aria-live="polite">{{ $totalPanduan }} panduan</span>
</div>

<div class="card laporan-card panduan-card">
    @if ($totalPanduan === 0)
        <div class="panduan-empty">
            <i class="ri-book-open-line" aria-hidden="true"></i>
            <p class="mb-0">Belum ada panduan yang tersedia.</p>
        </div>
    @else
        <ul class="panduan-list" id="panduanList">
            @foreach ($panduanList as $panduan)
                @php $documentType = \App\Support\DocumentType::fromFileName($panduan->nama_file); @endphp
                <li class="panduan-item" data-search="{{ mb_strtolower($panduan->judul.' '.$panduan->deskripsi) }}">
                    <span class="panduan-icon panduan-icon--{{ $documentType['tone'] }}" aria-hidden="true">
                        <i class="{{ $documentType['icon'] }}"></i>
                    </span>
                    <div class="panduan-body">
                        <h3 class="panduan-judul">{{ $panduan->judul }}</h3>
                        @if ($panduan->deskripsi)
                            <p class="panduan-deskripsi">{{ \Illuminate\Support\Str::limit($panduan->deskripsi, 160) }}</p>
                        @endif
                        <p class="panduan-meta">
                            {{ $documentType['label'] }} &middot; {{ \App\Support\FileSize::format($panduan->ukuran) }}
                            &middot; Diperbarui {{ $panduan->updated_at?->format('d-m-Y') }}
                        </p>
                    </div>
                    <a href="{{ route('panduan.unduh', $panduan->id_panduan) }}" class="btn btn-sm btn-primary panduan-unduh"
                        download rel="nofollow" aria-label="Unduh {{ $panduan->judul }}">
                        <i class="ri-download-2-line" aria-hidden="true"></i> Unduh
                    </a>
                </li>
            @endforeach
        </ul>
        <div id="panduanNoResult" class="panduan-empty d-none">
            <i class="ri-search-eye-line" aria-hidden="true"></i>
            <p class="mb-0">Panduan tidak ditemukan.</p>
        </div>
    @endif
</div>
