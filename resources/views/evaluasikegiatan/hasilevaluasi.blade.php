<x-table
    :ajax="route('admevaluasikegiatan.listdataserver')"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'kodept', 'name' => 'kodept', 'className' => 'text-center'],
        ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
        ['data' => 'pertanyaan', 'name' => 'pertanyaan'],
        ['data' => 'jawaban', 'name' => 'jawaban'],
    ]"
>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>Kode Perguruan Tinggi</th>
            <th>Nama Perguruan Tinggi</th>
            <th>Pertanyaan</th>
            <th>Jawaban</th>
        </tr>
    </x-slot:thead>
</x-table>