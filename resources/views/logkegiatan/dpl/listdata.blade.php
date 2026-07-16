<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th class="text-center" width="1">No</th>
                    <th class="text-center">Tanggal</th>
                    <th class="text-center">Nama</th>
                    <th class="text-center">Perguruan Tinggi</th>
                    <th class="text-center">Deskripsi</th>
                    <th class="text-center">KPI</th>
                    <th class="text-center" width="1">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<x-btn-export url="{{ url('admlogkegiatan/export') }}" />

<script type="text/javascript">
    $(function () {
        var table = $('#dataTable').DataTable({
            searching: true,
            lengthChange: false,
            processing: true,
            serverSide: true,
            ajax: "{{ route('admlogkegiatan.listdataserver') }}",
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', searchable: false},
                {data: 'tanggal', name: 'tanggal', className: 'text-center'},
                {data: 'nama_mahasiswa', name: 'nama_mahasiswa'},
                {data: 'nm_lemb', name: 'nm_lemb'},
                {
                    data: 'deskripsi',
                    name: 'deskripsi',
                    render: function (data, type, row) {
                        // Membuat sebuah div sementara untuk membersihkan tag HTML
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data;
                        // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                        var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                        return strippedText;
                    }
                },
                {data: 'nama_kpi', name: 'nama_kpi'},
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false, visible:false},
            ]
        });
    });
</script>