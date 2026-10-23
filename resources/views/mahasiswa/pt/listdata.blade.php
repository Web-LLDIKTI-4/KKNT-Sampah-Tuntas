<x-filter.lokasi-status />

<x-table
    :ajax="route('ptmahasiswa.listdataserver')"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'nim', 'name' => 'nim', 'className' => 'text-center'],
        ['data' => 'nama', 'name' => 'nama'],
        ['data' => 'email', 'name' => 'email'],
        ['data' => 'phone', 'name' => 'phone', 'className' => 'text-center'],
        ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
        ['data' => 'location_program', 'name' => 'location_program'],
        ['data' => 'ketua_status', 'name' => 'ketua_status', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'nama_kecamatan', 'name' => 'nama_kecamatan', 'emptyBadge' => 'Belum diisi'],
        ['data' => 'nama_desa', 'name' => 'nama_desa', 'emptyBadge' => 'Belum diisi'],
        ...(auth()->user()->role === 'pt'
            ? [['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false]]
            : []),
    ]"
>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>Nim</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Hp</th>
            <th>Perguruan Tinggi</th>
            <th>Lokasi Program KKN</th>
            <th>Ketua Kelompok</th>
            <th>Kecamatan</th>
            <th>Kelurahan/Desa</th>
            @if (auth()->user()->role === 'pt')
                <th>Aksi</th>
            @endif
        </tr>
    </x-slot:thead>
</x-table>
