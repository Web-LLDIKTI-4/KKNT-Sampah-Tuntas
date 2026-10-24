<x-bulk-toolbar :edit-url="url('dpl/bulk-edit')" :export-url="url('dpl/export-selected')" :delete-url="url('dpl/bulk-destroy')" />

<x-table
    :ajax="route('dpl.listdataserver')"
    :selectable="auth()->user()?->role === 'admin'"
    select-key="id_dpl"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'nidn', 'name' => 'nidn', 'className' => 'text-center'],
        ['data' => 'nama', 'name' => 'nama'],
        ['data' => 'email', 'name' => 'email'],
        ['data' => 'phone', 'name' => 'phone', 'className' => 'text-center'],
        ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
        ['data' => 'location_program', 'name' => 'location_program'],
        ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'searchable' => false],
    ]"

>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>NIDN</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Hp</th>
            <th>Perguruan Tinggi</th>
            <th>Lokasi Program KKN</th>
            <th width="1">Aksi</th>
        </tr>
    </x-slot:thead>
</x-table>