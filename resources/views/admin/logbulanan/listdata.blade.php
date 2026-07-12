<div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftarsilahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>

<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Bulan</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Deskripsi</th>
                    <th>#</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Bulan</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Deskripsi</th>
                    <th>#</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <hr>
    <a href="{{ url('admlogbulanan/export') }}"><i class="ri-file-excel-2-line"></i> Export data</a>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogbulanan.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
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
            {data: 'action', name: 'action', orderable: false, searchable: false},
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
                        if (index !== 0 && index !== 5) { // Skip column "No" (index 0)
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
        }
    });
  });
</script>