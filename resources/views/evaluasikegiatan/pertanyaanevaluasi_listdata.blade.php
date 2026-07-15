<x-table
    :ajax="route('admevaluasikegiatan.pertanyaanevaluasiserver')"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'searchable' => false],
        ['data' => 'pertanyaan', 'name' => 'pertanyaan'],
        ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'searchable' => false, 'visible' => false],
    ]"
>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>Pertanyaan</th>
        </tr>
    </x-slot:thead>
</x-table>