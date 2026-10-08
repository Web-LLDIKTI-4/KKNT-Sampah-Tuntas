
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    {{-- <th>Id LOG </th> --}}
                    <th>Tanggal</th>
                    <th>Deskripsi Kegiatan</th>
                    <th>Volume</th>
                    <th>Satuan</th>
                    <th>KPI</th>
                    <th>Tautan Bukti</th>
                    <th width="1">Aksi</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<x-button.export :url="route('logharian.export')" label="Export Log Harian" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('logkegiatan.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            // {data: 'id_log', name: 'id_log', visible: false}, 
            {data: 'tanggal', name: 'tanggal', className: 'text-center'},
            {data: 'deskripsi', name: 'deskripsi'},
            {data: 'volume', name: 'volume', className: 'text-end'},
            {data: 'satuan', name: 'satuan'},
            {data: 'nama_kpi', name: 'nama_kpi', orderable: false},
            {data: 'tautan', name: 'tautan', orderable: false, searchable: false},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
        decodeEntities: false
    });


  });
</script>