<p>
  <x-button>
    Data log bulanan bulan <b class="ms-1 me-1"> {{ Carbon\Carbon::create()->month((int) $bulan)->translatedFormat('F')}}</b> tahun <b class="ms-1 me-1"> {{ $tahun }}</b>
  </x-button>
</p>

<form id="form-tambah" method="post" action="{{ url('dpllaporan/insert') }}" data-ajax-form data-reload-url="{{ url('dpllaporan/listdata') }}" data-reload-target="#listdata">
    @csrf
    @method('PUT')
    <input type="hidden" name="bulan" value="{{$bulan}}">
    <input type="hidden" name="tahun" value="{{$tahun}}">
    <div class="alert alert-info">
        Panduan Pengisian : <br />
            1. Bagaimana pelaksanaan pembimbingan dan koordinasi yang telah dilakukan dengan mahasiswa KKN, pemerintah desa/kelurahan, kecamatan, serta mitra selama periode pelaporan? <br />
            2. Bagaimana perkembangan pelaksanaan program kerja mahasiswa berdasarkan hasil monitoring dan evaluasi yang telah dilakukan? Jelaskan capaian, progres, serta kesesuaiannya dengan rencana kegiatan. <br />
            3. Apa saja kendala atau permasalahan yang ditemukan selama proses pendampingan, dan langkah pembinaan, solusi, serta tindak lanjut apa yang telah dilakukan? <br />
            4. Bagaimana hasil evaluasi terhadap kinerja dan perkembangan mahasiswa KKN, baik dari aspek disiplin, kerja sama, komunikasi, kepemimpinan, profesionalisme, maupun pencapaian program kerja? <br />
            5. Apa rencana tindak lanjut, arahan, atau rekomendasi yang akan dilakukan pada periode berikutnya untuk mendukung keberhasilan pelaksanaan KKN? <br />
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea class="form-control summernote" name="deskripsi">{{ $isi?->deskripsi }}</textarea>
        <label>Deskripsi</label>
        <p id="wordCount">Jumlah kata: 0</p>
        <span id="deskripsi_error" class="text-danger"></span>
    </div>
    <x-form.input name="tautan" label="Tautan Laporan" :value="$isi?->tautan" input-class="form-control" />
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
        
<script>
$(function(){
    $('.summernote').summernote({
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['misc', ['undo', 'redo']],
            // Anda tidak perlu menyertakan 'insert' di sini
        ],
        minHeight: 300, // Atur ketinggian minimum editor teks di sini (dalam piksel)
        // Opsi lainnya disini
        callbacks: {
            onKeyup: function() {
                var text = $(this).summernote('code');
                var plainText = text.replace(/(<([^>]+)>)/ig,"").trim(); // Menghapus tag HTML dari teks
                var wordArray = plainText.match(/\b[a-zA-Z]+\b/g); // Mencocokkan kata-kata (hanya huruf)
                var wordCount = wordArray ? wordArray.length : 0; // Menghitung panjang array kata atau 0 jika null
                $('#wordCount').text("Jumlah kata: " + wordCount);
            },
            onPaste: function (e) {
                var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('text/html');
                e.preventDefault();
                var div = $('<div></div>');
                div.html(bufferText);
                div.find('*').removeAttr('style');
                setTimeout(function () {
                    document.execCommand('insertHtml', false, div.html());
                }, 10);
            }
        }
    });
});
</script>