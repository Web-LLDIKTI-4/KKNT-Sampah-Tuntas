<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id profile</th>
                    <th>Tahun</th>
                    <th>Nama Kelurahan/Desa</th>
                    <th>Potensi</th>
                    <th>Masalah</th>
                    @if ($canManage)
                        <th width="1">Aksi</th>
                    @endif
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
            @if ($canManage)
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            @endif
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    // Ambil teks lewat DOMParser (inert), lalu escape ulang saat dirender
                    var strippedText = new DOMParser().parseFromString(data || '', 'text/html').body.textContent || '';
                    return $('<div class="text-wrap"></div>').text(strippedText).prop('outerHTML');
                },
                targets: 4
            },
            {
                render: function (data, type, full, meta) {
                    // Ambil teks lewat DOMParser (inert), lalu escape ulang saat dirender
                    var strippedText = new DOMParser().parseFromString(data || '', 'text/html').body.textContent || '';
                    return $('<div class="text-wrap"></div>').text(strippedText).prop('outerHTML');
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
