{{-- @if(Auth::user()->role == 'dpl')
    <div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftar silahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>
@endif --}}

<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Nama DPL</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Jumlah</th>
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
            ajax: "{{ route('admlaporandpl.listdatagrouping') }}",
            language: {
                search: "",
                searchPlaceholder: "Cari...",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex' , className: 'text-center', orderable: false, searchable: false},
                {data: 'nama_dpl', name: 'nama_dpl'},
                {data: 'nama_pt', name: 'nama_pt'},
                {data: 'count_log', name: 'count_log', className: 'text-center', searchable: false},
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            ],
        });
    });
</script>