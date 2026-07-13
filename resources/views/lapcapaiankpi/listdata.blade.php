<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id kpicapaian</th>
                    <th>Desa</th>
                    <th>PJ Desa</th>
                    <th>Key performance indicator</th>
                    <th>Tahapan</th>
                    <th>Target Key performance indicator</th>
                    <th>Sudah Terlaksana</th>
                    <th>Tautan</th>
                    <th>Permasalahan</th>
                    <th>Solusi</th>
                    <th>Kendala</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
    <hr>
    <x-btn-export url="{{ url('lapcapaiankpi/export') }}" />
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('lapcapaiankpi.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_target', name: 'id_target', visible:false},
            {data: 'desa', name: 'desa'},
            {data: 'pjdesa', name: 'pjdesa'},
            {data: 'nama_kpi', name: 'nama_kpi'},
            {data: 'tahapan', name: 'tahapan'},               
            {
                data: 'nama_kpitarget',
                name: 'nama_kpitarget',
                render: function (data, type, row) {
                    // Membuat sebuah div sementara untuk membersihkan tag HTML
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" +strippedText+ "</div>";
                }
            },
            {data: 'status_capaian', name: 'status_capaian'},
            {data: 'tautan', name: 'tautan'},
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
            {data: 'action', name: 'action', orderable: false, searchable: false,visible:false,},
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