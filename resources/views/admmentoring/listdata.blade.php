<div class="row mb-3">
    <div class="col-12 col-md-6 col-lg-4">
        <label for="filterDpl" class="form-label small mb-1">DPL</label>
        <select id="filterDpl" class="form-select form-select-sm"
            data-table-filter="email_dpl" data-table-target="#dataTable">
            <option value="">Semua DPL</option>
            @foreach ($dpls as $dpl)
                <option value="{{ $dpl->email }}">{{ $dpl->nama }} – {{ $dpl->sp->nm_lemb ?? $dpl->kodept }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <label for="filterPt" class="form-label small mb-1">Perguruan Tinggi</label>
        <select id="filterPt" class="form-select form-select-sm"
            data-table-filter="kodept" data-table-target="#dataTable">
            <option value="">Semua Perguruan Tinggi</option>
            @foreach ($pts as $pt)
                <option value="{{ $pt->npsn }}">{{ $pt->nm_lemb }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>ID BIMBINGAN</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Prodi</th>
                    <th>Nama DPL</th>
                    <th>Email DPL</th>
                    <th>Ditugaskan</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $('#dataTable').DataTable({
            searching: true,
            lengthChange: true,
            processing: true,
            serverSide: true,
            searchDelay: 500,
            ajax: "{{ route('admmentoring.listdataserver') }}",
            language: {
                search: "",
                searchPlaceholder: "Cari nim, nama, DPL...",
                zeroRecords: "Belum ada mahasiswa yang ditugaskan",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'id_mentoring', name: 'id_mentoring', visible: false, orderable: false, searchable: false},
                {data: 'nim', name: 'nim', className: 'text-center'},
                {data: 'nama', name: 'nama'},
                {data: 'nm_lemb', name: 'nm_lemb'},
                {data: 'prodi', name: 'prodi'},
                {data: 'nama_dpl', name: 'nama_dpl'},
                {data: 'email_dpl', name: 'email_dpl'},
                {data: 'created_at', name: 'created_at', className: 'text-center text-nowrap'},
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            ],
            order: [[8, 'desc']],
        });
    });
</script>
