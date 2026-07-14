<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Bulan</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Deskripsi</th>
                    <th>#</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
    <hr>
</div>

<x-btn-export url="{{ url('admlogbulanan/export') }}" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogbulanan.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'nama_bulan', name: 'nama_bulan'},
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
                    return "<div class='text-wrap width-200'>" +strippedText+ "</div>";
                }
            },
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
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