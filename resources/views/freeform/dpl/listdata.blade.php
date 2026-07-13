
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered user_datatable" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>ID FREEFORM</th>
                    <th>Aksi</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Prodi</th>
                    <th>Free Form</th>
                    <th>Nilai DPL</th>
                    <th>Nilai DPA</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>ID FREEFORM</th>
                    <th>Aksi</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Prodi</th>
                    <th>Free Form</th>
                    <th>Nilai DPL</th>
                    <th>Nilai DPA</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('dplfreeform.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_freeform', name: 'id_freeform', visible:false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'prodi', name: 'prodi'},
            {data: 'freeform', name: 'freeform'},
            {data: 'nilai_dpl', name: 'nilai_dpl'},
            {data: 'nilai_dpa', name: 'nilai_dpa'},
        ],
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    // Periksa apakah footer ada sebelum mencoba mengakses propertinya
                    var footer = column.footer(); // Dapatkan footer kolom

                    // Periksa apakah footer ada sebelum mencoba mengakses propertinya
                    if (footer) {
                        var title = column.footer().textContent;
        
                        // Create input element and add event listener
                        if (index !== 0 && index !== 2) { // Skip column "No" (index 0)
                            $('<input type="text" class="form-control form-control-sm p-1" placeholder="Search ' + title + '" />')
                                .appendTo($(column.footer()).empty())
                                .on('keyup change clear', function () {
                                    if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                    }
                                });
                        }
                    }
                });
        },
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        },
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
            let id_freeform = data.id_freeform;
            const dString = "id_freeform="+id_freeform;
            $.ajax({
                url: 'dplfreeform/destroy',
                method: 'PUT',
                data:dString,
                headers: {
                    'X-CSRF-TOKEN': csrfToken // Sertakan CSRF token dalam header
                },
                success: function(response) {
                    if (response && response.message) {
                        // Jika pesan sukses, tampilkan pesan berhasil
                        $.notify(response.message, {
                            type: 'success',
                            animate: {
                                enter: 'animated rollIn',
                                exit: 'animated rollOut'
                            },
                            z_index: 2000
                        });
                        table.ajax.reload();
                    }
                    
                    
                },
                error: function(xhr, status, error) {
                    // Tangani kesalahan seperti CSRF token mismatch atau kesalahan server
                    console.log(xhr.status); // Kode status HTTP
                    console.log(xhr.responseText); // Pesan kesalahan dari server
                    console.log(error); // Pesan kesalahan bawaan dari jQuery
                    $.notify('Terjadi kesalahan saat menghapus data', {
                        type: 'danger',
                        animate: {
                            enter: 'animated rollIn',
                            exit: 'animated rollOut'
                        },
                        z_index: 2000
                    });
                }
            });
        }
    });
  });
</script>