<x-datatable id="dataTable" tableClass="table table-sm table-bordered" :autoInit="false">
    <x-slot:thead>
        <tr>
            <th width="1">No</th>              <!-- 1 -->
            <th>Kode Perguruan Tinggi</th>                     <!-- 2 -->
            <th>Nama Perguruan Tinggi</th>      <!-- 3 -->
            {{-- <th>Alamat</th>                     <!-- 4 --> --}}
        </tr>
    </x-slot:thead>
</x-datatable>

<script type="text/javascript">
$(function () {
    $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        lengthChange: true,
        ajax: "{{ route('perguruantinggi.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'npsn', name: 'npsn', className: 'text-center'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            // {data: 'jln', name: 'jln'},
        ],
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang ditemukan",
            infoEmpty: "Tidak ada data yang tersedia",
            emptyTable: "Tidak ada data yang tersedia di tabel"
        },
    });
});
</script>