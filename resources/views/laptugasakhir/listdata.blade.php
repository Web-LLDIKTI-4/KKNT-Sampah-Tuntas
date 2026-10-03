<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('laptugasakhir.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_tugasakhir', 'name' => 'id_tugasakhir', 'visible' => false],
                ['data' => 'nim', 'name' => 'nim', 'className' => 'text-center'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'tautan', 'name' => 'tautan'],
                ['data' => 'nilai_dpl', 'name' => 'nilai_dpl', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
            ]"
        >
                    <x-slot:thead>
                        <tr>
                            <th class="text-center" width="1">No</th>
                            <th class="text-center">Id Lap</th>
                            <th class="text-center">NIM</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Perguruan Tinggi</th>
                            <th class="text-center">Laporan</th>
                            <th class="text-center" width="1">Nilai</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>

<x-button.export url="{{ url('laptugasakhir/export') }}" />
