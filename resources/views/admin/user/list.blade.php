<div class="table-responsive">
<table class="table table-bordered table-sm" id="dataTable-user">
    <thead>
        <tr>
            <th>No</th>
            <th>Username</th>
            <th>Nama</th>
            <th>Nim/NIDN</th>
            <th>Perguruan Tinggi</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @if($data->isEmpty())
            
        @else
            @foreach($data as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->name }}</td>
                    @if ($row->role === 'mahasiswa' && $row->mahasiswa)
                        <td>{{ $row->mahasiswa->nim }}</td>
                        <td>
                            @if($row->mahasiswa->sp && $row->mahasiswa->sp->nm_lemb)
                                {{ $row->mahasiswa->sp->nm_lemb }}
                            @else
                                {{ $row->mahasiswa->kodept }}
                            @endif
                        </td>
                    @elseif ($row->role === 'dpl' && $row->dpl)
                        <td>{{ $row->dpl->nidn }}</td>
                        <td>
                            @if($row->dpl->sp && $row->dpl->sp->nm_lemb)
                                {{ $row->dpl->sp->nm_lemb }}
                            @else
                                {{ $row->dpl->kodept }}
                            @endif
                        </td>    
                    @else
                        <td>-</td>
                        <td>-</td>
                    @endif
                    <td>{{ $row->role }}</td>
                    <td>
                        @if($row->role != "pt")
                            <a href="#modalku" data-bs-toggle="modal" class="modalButton" data-src="{{ url('user/edit/'.$row->id) }}" title="Edit User">edit</a>
                        @else
                        <a href="#modalku" data-bs-toggle="modal" class="modalButton" data-src="{{ url('user/edituserpt/'.$row->id) }}" title="Edit User">edit</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
    <tfoot>
        <tr>
            <th>No</th>
            <th>Username</th>
            <th>Nama</th>
            <th>Nim/NIDN</th>
            <th>Perguruan Tinggi</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>
    </tfoot>
</table>
</div>
<script>
    $(function () {

    let table = $('#dataTable-user').DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        serverSide: false,
        language: {
            "zeroRecords": "Tidak ada data yang ditemukan",
            "infoEmpty": "Tidak ada data yang tersedia",
            "sEmptyTable": "Tidak ada data yang tersedia di tabel"
        },
        columnDefs: [
            { targets: 'no-sort', orderable: false } // Tambahkan class 'no-sort' pada kolom 'Aksi'
        ],
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    var title = column.footer().textContent;
    
                    // Create input element and add event listener
                    if (index !== 0 && index !== 6) { // Skip column "No" (index 0)
                        $('<input type="text" class="form-control form-control-sm p-1" placeholder="Search ' + title + '" />')
                            .appendTo($(column.footer()).empty())
                            .on('keyup change clear', function () {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    }
                });
        }
    });
})
</script>
