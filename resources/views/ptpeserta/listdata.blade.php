
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kode Perguruan Tinggi</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Jumlah Mahasiswa</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('ptpeserta.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'kodept', name: 'kodept'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'jumlah_mhs', name: 'jumlah_mhs'},
        ],
        // Menambahkan opsi untuk mencegah escape HTML oleh DataTables
        decodeEntities: false
    });

  });
</script>