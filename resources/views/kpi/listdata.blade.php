
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id KPI </th>
                    <th>Key performance indicator </th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('kpi.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_kpi', name: 'id_kpi', visible: false}, 
            {data: 'nama_kpi', name: 'nama_kpi'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
    });


    // Menangani klik tombol hapus
    $("body").on('click','[id^=hapus]', function() {
        // Mendapatkan baris yang diklik
        var data = table.row($(this).parents('tr')).data();
        // Lakukan apa pun yang diperlukan untuk mengonfirmasi pengguna sebelum menghapus data
        if (confirm('Anda yakin ingin menghapus data ini?')) {
            // Lakukan permintaan AJAX untuk menghapus data
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            let id_kpi = data.id_kpi;
            const dString = "id_kpi="+id_kpi;
            $.ajax({
                url: 'kpi/destroy',
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

                    // Menampilkan pesan kesalahan menggunakan $.notify
                    var errorMessage = 'Terjadi kesalahan saat menghapus data: ' + error;

                    // Tambahkan informasi tambahan dari responseText jika ada
                    if (xhr.responseText) {
                        errorMessage += ' - ' + xhr.responseText;
                    }

                    toastr.warning(response.message)		
                }

            });
        }
    });
  });
</script>