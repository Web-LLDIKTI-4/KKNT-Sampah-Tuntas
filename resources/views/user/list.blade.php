<div class="table-responsive">
<x-table
    id="dataTable-user"
    :ajax="route('user.listdataserver', $filters)"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'email', 'name' => 'email'],
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'id_number', 'name' => 'id_number', 'className' => 'text-center'],
        ['data' => 'pt_name', 'name' => 'pt_name'],
        ['data' => 'role', 'name' => 'role'],
        ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
    ]"
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

<x-button.export url="{{ route('user.export', $filters) }}" />
