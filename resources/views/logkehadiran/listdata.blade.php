<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Jam Masuk</th>
                    <th>Lokasi Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Lokasi Pulang</th>
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
        lengthChange: false,
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
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'tanggal', name: 'tanggal', className: 'text-center'},
            {data: 'nama_mahasiswa', name: 'nama_mahasiswa'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'waktu_masuk', name: 'waktu_masuk', className: 'text-center'},
            {data: 'coordinates_datang', name: 'coordinates_datang', className: 'text-center'},
            {data: 'waktu_pulang', name: 'waktu_pulang', className: 'text-center'},
            {data: 'coordinates_pulang', name: 'coordinates_pulang', className: 'text-center'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false, visible:false},
        ],
    });
  });
</script>