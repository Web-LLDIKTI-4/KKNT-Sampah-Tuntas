
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    {{-- <th>Id LOG </th> --}}
                    <th width="100">Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Volume</th>
                    <th>Satuan</th>
                    <th>KPI</th>
                    <th width="1">Aksi</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<x-button.export url="{{ url('logkegiatan/export') }}" />

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
            {
                data: 'deskripsi',
                name: 'deskripsi',
                render: function (data, type, row) {
                    // Ambil teks lewat DOMParser (inert), lalu escape ulang saat dirender
                    var strippedText = new DOMParser().parseFromString(data || '', 'text/html').body.textContent || '';
                    return $('<div></div>').text(strippedText).html();
                }
            },
            {data: 'volume', name: 'volume', className: 'text-center'},
            {data: 'satuan', name: 'satuan', className: 'text-center'},
            {data: 'nama_kpi', name: 'nama_kpi'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
        // Menambahkan opsi untuk mencegah escape HTML oleh DataTables
        decodeEntities: false
    });


  });
</script>