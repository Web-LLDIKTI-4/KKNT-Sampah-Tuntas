<x-table
    :ajax="route('admevaluasikegiatan.listdataserver')"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'kodept', 'name' => 'kodept', 'className' => 'text-center'],
        ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
        ['data' => 'pertanyaan', 'name' => 'pertanyaan', 'className' => 'text-wrap'],
        ['data' => 'jawaban', 'name' => 'jawaban', 'className' => 'text-wrap'],
    ]"
>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>Kode Perguruan Tinggi</th>
            <th>Nama Perguruan Tinggi</th>
            <th class="text-wrap">Pertanyaan</th>
            <th class="text-wrap">Jawaban</th>
        </tr>
    </x-slot:thead>
</x-table>