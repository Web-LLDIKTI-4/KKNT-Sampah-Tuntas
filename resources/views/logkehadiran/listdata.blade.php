<div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftarsilahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>

<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Pulang</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogkehadiran.listdataserver') }}",
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
            {data: 'waktu_masuk', name: 'waktu_masuk'},
            {data: 'waktu_pulang', name: 'waktu_pulang'},
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:false},
        ],
    });
  });
</script>