
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>ID FREEFORM</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Nama PT</th>
                    <th>Prodi.</th>
                    <th>Free Form</th>
                    <th>Nilai DPL</th>
                    <th>Nilai DPA</th>
                    <th>Nilai Akhir</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-btn-export url="{{ url('admfreeform/export') }}" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admfreeform.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_freeform', name: 'id_freeform', visible:false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'prodi', name: 'prodi'},
            {data: 'freeform', name: 'freeform'},
            {data: 'nilai_dpl', name: 'nilai_dpl', className: 'text-center'},
            {data: 'nilai_dpa', name: 'nilai_dpa', className: 'text-center'},
            {data: 'nilai_akhir', name: 'nilai_akhir', className: 'text-center'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false,visible:false},
        ],
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