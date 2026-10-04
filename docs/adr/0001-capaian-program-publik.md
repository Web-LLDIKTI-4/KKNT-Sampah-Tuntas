# ADR 0001 — Capaian Program publik: klaster, ambang KPI, unduh PNG

Status: Diusulkan (menunggu konfirmasi user, lihat `.claude/handoff/revisi-publik/01-analyst.md` §5)
Tanggal: 2026-10-04

## Konteks
Revisi halaman login publik: filter klaster, ambang hijau `> 20%`, tombol unduh PNG tabel. Partial `laporan/_drilldown` dipakai juga oleh dashboard internal.

## Keputusan
1. **Filter klaster = filter tampilan per baris** (kecamatan/kelurahan/PT disaring berdasarkan klaster persen masing-masing). Angka tidak dihitung ulang. Ditolak: pola `kodept_in` dari Rekap Sampah (angka berubah per filter, membingungkan publik).
2. **Ambang klaster di satu sumber `Kpisampah`**: hijau `> 20`, kuning `10 – ≤ 20`, merah `< 10`; berlaku global (rekap, export, dashboard).
3. **% kumulatif kota/kab** = SUM(pengurangan)/SUM(timbulan) per `mahasiswa.location_program` ketua, bukan rata-rata persen.
4. **Unduh PNG di sisi klien** dengan `html-to-image` sebagai file vendor lokal, di-load lazy. Ditolak: render server (Chrome headless, tidak muat di VPS 1 GB), `html2canvas` (lebih berat), canvas manual (kode banyak).

## Konsekuensi
- Tanpa migration. Perlu `cache:clear` saat deploy.
- Nilai tepat 20,00% berpindah dari hijau ke kuning di semua laporan.
- Satu file JS vendor baru (perlu persetujuan user).
