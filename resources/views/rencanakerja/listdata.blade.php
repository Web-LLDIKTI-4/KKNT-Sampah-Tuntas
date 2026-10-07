@php($canAct = in_array(Auth::user()->role, ['admin', 'pt']))
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    @if ($showPt)
                        <th>Perguruan Tinggi</th>
                    @endif
                    <th>Judul</th>
                    <th>Tahun</th>
                    <th>File</th>
                    <th>Diunggah</th>
                    @if ($canAct)
                        <th width="1">Aksi</th>
                    @endif
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var columns = [
        {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
        @if ($showPt)
        {data: 'pt', name: 'pt.nm_lemb'},
        @endif
        {data: 'judul', name: 'judul'},
        {data: 'tahun', name: 'tahun', className: 'text-center'},
        {data: 'file', name: 'nama_file'},
        {data: 'created_at', name: 'created_at', searchable: false},
        @if ($canAct)
        {data: 'action', name: 'action', orderable: false, searchable: false},
        @endif
    ];

    $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        order: [[columns.length - {{ $canAct ? 2 : 1 }}, 'desc']],
        ajax: "{{ route('rencanakerja.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: @json($showPt ? 'Cari PT, judul, atau nama file...' : 'Cari judul atau nama file...'),
            zeroRecords: "Belum ada rencana kerja.",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: columns
    });
  });
</script>
