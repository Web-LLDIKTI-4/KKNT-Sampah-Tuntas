<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th class="text-center" width="1">No</th>
                    <th class="text-center">NIM</th>
                    <th class="text-center">Nama</th>
                    <th class="text-center">Nama Perguruan Tinggi</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-center" width="1">Aksi</th>
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
            ajax: "{{ route('admlogkehadiran.listdatagrouping') }}",
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'nim', name: 'nim', className: 'text-center'},
                {data: 'nama_mahasiswa', name: 'nama_mahasiswa'},
                {data: 'nm_lemb', name: 'nm_lemb'},
                {data: 'count_log', name: 'count_log', className: 'text-center', searchable: false},
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            ],
        });
    });
</script>