<x-table
    :ajax="route('ptmahasiswa.listdataserver')"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'nim', 'name' => 'nim'],
        ['data' => 'nama', 'name' => 'nama'],
        ['data' => 'email', 'name' => 'email'],
        ['data' => 'phone', 'name' => 'phone'],
        ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
        ['data' => 'location_program', 'name' => 'location_program'],
        ['data' => 'ketua_kelompok', 'name' => 'ketua_kelompok', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
    ]"
    :check="[7]"
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
        </tr>
    </x-slot:thead>
</x-table>
