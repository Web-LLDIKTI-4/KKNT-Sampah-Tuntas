<div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftarsilahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Deskripsi</th>
                    <th>KPI</th>
                    <th width="1">Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <hr>
    <x-btn-export url="{{ url('admlogkegiatan/export') }}" />
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogkegiatan.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'tanggal', name: 'tanggal'},
            {data: 'nama_mahasiswa', name: 'nama_mahasiswa'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {
                data: 'deskripsi',
                name: 'deskripsi',
                render: function (data, type, row) {
                    // Membuat sebuah div sementara untuk membersihkan tag HTML
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return strippedText;
                }
            },
            {data: 'nama_kpi', name: 'nama_kpi'},
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:false},
        ]
    });
  });
</script>