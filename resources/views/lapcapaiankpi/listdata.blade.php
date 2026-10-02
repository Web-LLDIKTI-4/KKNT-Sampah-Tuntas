<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    {{-- <th>Id kpicapaian</th> --}}
                    <th>Lokasi Kegiatan</th>
                    <th>Ketua Kelompok</th>
                    <th>Nama KPI</th>
                    <th>Permasalahan</th>
                    <th>Solusi</th>
                    <th>Kebutuhan Dukungan</th>
                    <th>Tindak Lanjut</th>
                    <th>Tautan</th>
                    {{-- <th width="1">Aksi</th> --}}
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-button.export url="{{ url('lapcapaiankpi/export') }}" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('lapcapaiankpi.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex' , className: 'text-center', orderable: false, searchable: false},
            // {data: 'id_target', name: 'id_target', visible:false},
            {data: 'lokasi', name: 'lokasi'},
            {data: 'pjdesa', name: 'pjdesa'},
            {data: 'nama_kpi', name: 'nama_kpi'},
            {
                data: 'permasalahan',
                name: 'permasalahan',
                render: function (data, type, row) {
                    // Membuat sebuah div sementara untuk membersihkan tag HTML
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" +strippedText+ "</div>";
                }
            },
            {
                data: 'solusi',
                name: 'solusi',
                render: function (data, type, row) {
                    // Membuat sebuah div sementara untuk membersihkan tag HTML
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" +strippedText+ "</div>";
                }
            },
            {
                data: 'kendala',
                name: 'kendala',
                render: function (data, type, row) {
                    // Membuat sebuah div sementara untuk membersihkan tag HTML
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" +strippedText+ "</div>";
                }
            },
            {data: 'status_capaian', name: 'status_capaian', className: 'text-center'},
            {data: 'tautan', name: 'tautan'},
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