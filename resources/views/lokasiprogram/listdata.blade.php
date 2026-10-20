<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id</th>
                    <th>Gambar</th>
                    <th>Nama Lokasi</th>
                    <th>Koefisien Timbulan Sampah</th>
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
        searchDelay: 500,
        ajax: "{{ route('lokasiprogram.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id', name: 'id', visible:false},
            {data: 'gambar', name: 'gambar', orderable: false, searchable: false},
            {data: 'nama_lokasi', name: 'nama_lokasi'},
            {data: 'koefisien_timbulan_sampah', name: 'koefisien_timbulan_sampah', className: 'text-end', searchable: false, render: function (data) {
                var num = parseFloat(data);
                return isNaN(num) ? '-' : num.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 4});
            }},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    return "<div class='text-wrap'>" + data + "</div>";
                },
                targets: 3
            }
        ],
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        }
    });
  });
</script>
