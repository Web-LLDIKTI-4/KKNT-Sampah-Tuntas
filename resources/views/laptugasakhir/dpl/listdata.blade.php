<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            table-class="table table-sm"
            thead-class=""
            ajax="{{ route('dpllaptugasakhir.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'searchable' => false],
                ['data' => 'id_tugasakhir', 'name' => 'id_tugasakhir', 'visible' => false],
                ['data' => 'nim', 'name' => 'nim'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'tautan', 'name' => 'tautan'],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
            ]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Id Lap</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Nama Perguruan Tinggi</th>
                            <th>Laporan</th>
                            <th class="text-center" width="100">Nilai</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>

<x-button.export url="{{ url('dpllaptugasakhir/export') }}" />
