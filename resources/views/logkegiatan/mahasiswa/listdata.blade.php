
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id LOG </th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Volume</th>
                    <th>Satuan</th>
                    <th>KPI</th>
                    <th width="1">Aksi</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<x-btn-export url="#" title="Export Data" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('logkegiatan.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_log', name: 'id_log', visible: false}, 
            {data: 'tanggal', name: 'tanggal'},
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
            {data: 'volume', name: 'volume'},
            {data: 'satuan', name: 'satuan'},
            {data: 'nama_kpi', name: 'nama_kpi'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
        // Menambahkan opsi untuk mencegah escape HTML oleh DataTables
        decodeEntities: false
    });


    // Menangani klik tombol hapus
    $("body").on('click','[id^=hapus]', function() {
        // Mendapatkan baris yang diklik
        var data = table.row($(this).parents('tr')).data();
        // Lakukan apa pun yang diperlukan untuk mengonfirmasi pengguna sebelum menghapus data
        if (confirm('Anda yakin ingin menghapus data ini?')) {
            // Lakukan permintaan AJAX untuk menghapus data
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            let id_log = data.id_log;
            const dString = "id_log="+id_log;
            $.ajax({
                url: 'logkegiatan/destroy',
                method: 'PUT',
                data:dString,
                headers: {
                    'X-CSRF-TOKEN': csrfToken // Sertakan CSRF token dalam header
                },
                success: function(response) {
                    if (response && response.message) {
                        // Jika pesan sukses, tampilkan pesan berhasil
                        toastr.success(response.message)			
                        table.ajax.reload();
                    }
                    
                    
                },
                error: function(xhr, status, error) {
                    // Tangani kesalahan seperti CSRF token mismatch atau kesalahan server
                    console.log(xhr.status); // Kode status HTTP
                    console.log(xhr.responseText); // Pesan kesalahan dari server
                    console.log(error); // Pesan kesalahan bawaan dari jQuery
                    toastr.warning(response.error)			
                }
            });
        }
    });
  });
</script>