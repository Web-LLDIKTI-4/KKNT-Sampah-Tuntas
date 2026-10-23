<div class="row mb-2">
    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
        <label for="filter-mobilisasi-pt" class="form-label small mb-1">Status Mobilisasi</label>
        <select id="filter-mobilisasi-pt" class="form-select form-select-sm" data-no-search>
            <option value="">Semua</option>
            <option value="sudah">Sudah</option>
            <option value="belum">Belum</option>
        </select>
    </div>
</div>
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Nama Kecamatan</th>
                    <th>Nama Kelurahan/Desa</th>
                    <th>Ketua Kelompok</th>
                    <th>Status Mobilisasi</th>
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
        ajax: {
            url: "{{ route('ptpjdesa.listdataserver') }}",
            data: function (d) { d.mobilisasi = $('#filter-mobilisasi-pt').val(); }
        },
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Belum ada ketua kelompok dari perguruan tinggi Anda",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'kecamatan', name: 'kecamatan'},
            {data: 'desa', name: 'desa', render: function (data) { return "<div class='text-wrap'>" + data + "</div>"; }},
            {data: 'pjdesa', name: 'pjdesa'},
            {data: 'mobilisasi', name: 'mobilisasi', className: 'text-center', searchable: false},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
    });
    $('#filter-mobilisasi-pt').on('change', function () { table.ajax.reload(); });
  });
</script>
