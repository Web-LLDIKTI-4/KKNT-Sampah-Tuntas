<div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftarsilahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>

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
    <x-btn-export url="{{ url('admlogbulanan/export') }}" />
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