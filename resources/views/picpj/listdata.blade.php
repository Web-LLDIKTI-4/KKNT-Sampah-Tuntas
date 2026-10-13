<x-table
    :ajax="route('picpj.listdataserver')"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'kodept', 'name' => 'kodept', 'className' => 'text-center'],
        ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
        ['data' => 'no_tel', 'name' => 'no_tel', 'className' => 'text-center'],
    ]"
>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>Kode PT</th>
            <th>Perguruan Tinggi</th>
            <th>No. Kontak</th>
        </tr>
    </x-slot:thead>
</x-table>
