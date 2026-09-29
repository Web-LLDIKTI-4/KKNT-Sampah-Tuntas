<div class="table-responsive">
<table class="table table-bordered table-sm" id="dataTable-user">
    <thead>
        <tr>
            <th width="1">No</th>
            <th>Nama Pengguna</th>
            <th>Nama</th>
            <th>Nim/NIDN</th>
            <th>Perguruan Tinggi</th>
            <th>Peran</th>
            <th width="1">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @if($data->isEmpty())
            
        @else
            @foreach($data as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->name }}</td>
                    @if ($row->role === 'mahasiswa' && $row->mahasiswa)
                        <td class="text-center">{{ $row->mahasiswa->nim }}</td>
                        <td>
                            @if($row->mahasiswa->sp && $row->mahasiswa->sp->nm_lemb)
                                {{ $row->mahasiswa->sp->nm_lemb }}
                            @else
                                {{ $row->mahasiswa->kodept }}
                            @endif
                        </td>
                    @elseif ($row->role === 'dpl' && $row->dpl)
                        <td class="text-center">{{ $row->dpl->nidn }}</td>
                        <td>
                            @if($row->dpl->sp && $row->dpl->sp->nm_lemb)
                                {{ $row->dpl->sp->nm_lemb }}
                            @else
                                {{ $row->dpl->kodept }}
                            @endif
                        </td>  
                    @elseif ($row->role === 'pt' && $row->pt)
                        <td class="text-center">-</td>
                        <td>
                            {{ $row->pt->nm_lemb }}
                        </td> 
                    @else
                        <td class="text-center">-</td>
                        <td>-</td>
                    @endif
                    <td>{{ $row->role }}</td>
                    <td class="text-center no-sort">
                        @if($row->role === 'kepala')
                            <x-action-data urlEdit="{{ url('user/edituserkepala/'.$row->id) }}" />
                        @elseif($row->role != "pt")
                            <x-action-data urlEdit="{{ url('user/edit/'.$row->id) }}" />
                        @else
                            <x-action-data urlEdit="{{ url('user/edituserpt/'.$row->id) }}" />
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
</div>

<x-btn-export url="{{ route('user.export') }}" />

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
                "search": "",
                "searchPlaceholder": "Cari...",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "infoEmpty": "Tidak ada data yang tersedia"
            },
            columnDefs: [
                { targets: 'no-sort', orderable: false } // Tambahkan class 'no-sort' pada kolom 'Aksi'
            ],
        });
    })
</script>
