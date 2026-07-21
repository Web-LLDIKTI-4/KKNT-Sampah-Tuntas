
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>ID MENTORING</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Prodi</th>
                    <th>Aksi</th>
                    <th>Nilai Log Bulanan</th>
                    {{-- <th>Nilai & Free Form</th> --}}
                    <th>Tugas akhir</th>
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
            ajax: "{{ route('dplmentoring.listdataserver') }}",
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'id_mentoring', name: 'id_mentoring', visible:false},
                {data: 'nim', name: 'nim', className: 'text-center'},
                {data: 'nama', name: 'nama'},
                {data: 'nm_lemb', name: 'nm_lemb'},
                {data: 'prodi', name: 'prodi'},
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
                {data: 'rekapnilai', name: 'rekapnilai', className: 'text-center'},
                // {data: 'nilai_freeform', name: 'nilai_freeform', className: 'text-center'},
                {data: 'tugasakhir', name: 'tugasakhir', className: 'text-center'},
            ],
            // Menambahkan opsi untuk mencegah escape HTML oleh DataTables
            decodeEntities: false
        });
    });
</script>