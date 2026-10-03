<div class="table-responsive">
<x-table
    :ajax="route('user.listdataserver')"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'searchable' => false, 'orderable' => false],
        ['data' => 'email', 'name' => 'email'],
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'nim_nidn', 'name' => 'nim_nidn', 'className' => 'text-center', 'orderable' => false],
        ['data' => 'nm_lemb', 'name' => 'nm_lemb', 'orderable' => false],
        ['data' => 'role', 'name' => 'role'],
        ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'searchable' => false, 'orderable' => false],
    ]"
    :order="[]"
>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>Nama Pengguna</th>
            <th>Nama</th>
            <th>Nim/NIDN</th>
            <th>Perguruan Tinggi</th>
            <th>Peran</th>
            <th width="1">Aksi</th>
        </tr>
    </x-slot:thead>
</x-table>
</div>

<x-button.export url="{{ route('user.export') }}" />
