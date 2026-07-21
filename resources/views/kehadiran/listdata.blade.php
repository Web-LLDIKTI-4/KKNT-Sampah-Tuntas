<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Status kehadiran</th>
                    <th>Jam Masuk</th>
                    <th>Lokasi Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Lokasi Pulang</th>
                    <th width="1">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- T Body Here --}}
            </tbody>
        </table>
    </div>
</div>
<hr>

<x-btn-export url="{{ url('logkehadiran/export') }}" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('logkehadiran.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'tanggal', name: 'tanggal', className: 'text-center'},
            {data: 'status_kehadiran', name: 'status_kehadiran', className: 'text-center'},
            {data: 'waktu_masuk', name: 'waktu_masuk', className: 'text-center'},
            {data: 'coordinates_datang', name: 'coordinates_datang', className: 'text-center'},
            {data: 'waktu_pulang', name: 'waktu_pulang', className: 'text-center'},
            {data: 'coordinates_pulang', name: 'coordinates_pulang', className: 'text-center'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false, visible:false},
        ]
    });
  });
</script>