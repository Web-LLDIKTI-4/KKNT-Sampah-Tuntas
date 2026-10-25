<x-filter.lokasi-status>
    <x-filter.ketua-kelompok />
</x-filter.lokasi-status>

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
        ['data' => 'ketua_kelompok', 'name' => 'ketua_kelompok', 'className' => 'text-center', 'orderable' => false, 'searchable' => false, 'boolIcon' => ['Ketua kelompok', 'Bukan ketua']],
        ['data' => 'nama_kecamatan', 'name' => 'nama_kecamatan', 'emptyIcon' => 'Kecamatan belum diisi'],
        ['data' => 'nama_desa', 'name' => 'nama_desa', 'emptyIcon' => 'Kelurahan/Desa belum diisi'],
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
            <th>Status Ketua Kelompok</th>
            <th>Kecamatan</th>
            <th>Kelurahan/Desa</th>
            @if (auth()->user()->role === 'pt')
                <th>Aksi</th>
            @endif
        </tr>
    </x-slot:thead>
</x-table>
