<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Status kehadiran</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Aksi</th>
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
        processing: true,
        serverSide: true,
        ajax: "{{ route('logkehadiran.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'tanggal', name: 'tanggal'},
            {data: 'status_kehadiran', name: 'status_kehadiran'},
            {data: 'waktu_masuk', name: 'waktu_masuk'},
            {data: 'waktu_pulang', name: 'waktu_pulang'},
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:false},
        ]
    });
  });
</script>