
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('admstructureform.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_konversi', 'name' => 'id_konversi', 'visible' => false],
                ['data' => 'nim', 'name' => 'nim'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'prodi', 'name' => 'prodi'],
                ['data' => 'matakuliah', 'name' => 'matakuliah'],
                ['data' => 'sks', 'name' => 'sks', 'className' => 'text-center'],
                ['data' => 'nilai_dpl', 'name' => 'nilai_dpl', 'className' => 'text-center'],
                ['data' => 'nilai_dpa', 'name' => 'nilai_dpa', 'className' => 'text-center', 'visible' => false],
                ['data' => 'nilai_akhir', 'name' => 'nilai_akhir', 'className' => 'text-center'],
            ]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>ID KONVERSI</th>
                            <th>Nim</th>
                            <th>Nama</th>
                            <th>Nama Perguruan Tinggi</th>
                            <th>Prodi.</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Nilai DPL</th>
                            <th>Nilai DPA</th>
                            <th>Nilai Akhir</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>

<x-button.export url="{{ url('admstructureform/export') }}" />
