<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kode PT</th>
                    <th>Nama PT</th>
                    <th>Lokasi Program</th>
                    <th>Tanggal Mulai</th>
                    <th>Tanggal Selesai</th>
                    <th>Durasi</th>
                    <th>Terakhir Diperbarui</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    // Read-only: tanpa kolom aksi
    $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        searchDelay: 500,
        order: [[2, 'asc']],
        ajax: "{{ route('pelaksanaankkn.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: 'Cari kode atau nama PT...',
            zeroRecords: "Belum ada data pelaksanaan KKN.",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'kodept', name: 'kodept', className: 'text-nowrap'},
            {data: 'nama_pt', name: 'nama_pt'},
            {data: 'lokasi', name: 'lokasi', orderable: false, searchable: false},
            {data: 'tanggal_mulai', name: 'tanggal_mulai', className: 'text-center text-nowrap', searchable: false},
            {data: 'tanggal_selesai', name: 'tanggal_selesai', className: 'text-center text-nowrap', searchable: false},
            {data: 'durasi_hari', name: 'durasi_hari', className: 'text-center text-nowrap', searchable: false},
            {data: 'updated_at', name: 'updated_at', className: 'text-center text-nowrap', searchable: false},
        ]
    });
  });
</script>
