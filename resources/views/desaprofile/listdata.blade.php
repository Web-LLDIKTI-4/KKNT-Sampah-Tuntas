<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id profile</th>
                    <th>Tahun</th>
                    <th>Nama Desa / Kelurahan</th>
                    <th>Potensi</th>
                    <th>Masalah</th>
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
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('desaprofile.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_profile', name: 'id_profile', visible:false},
            {data: 'tahun', name: 'tahun', className: 'text-center'},
            {data: 'desa', name: 'desa'},
            {data: 'potensi', name: 'potensi'},
            {data: 'masalah', name: 'masalah'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" + strippedText + "</div>";
                },
                targets: 4
            },
            {
                render: function (data, type, full, meta) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" + strippedText + "</div>";
                },
                targets: 5
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