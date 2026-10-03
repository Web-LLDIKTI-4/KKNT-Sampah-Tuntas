
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('admfreeform.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_freeform', 'name' => 'id_freeform', 'visible' => false],
                ['data' => 'nim', 'name' => 'nim'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'prodi', 'name' => 'prodi'],
                ['data' => 'freeform', 'name' => 'freeform'],
                ['data' => 'nilai_dpl', 'name' => 'nilai_dpl', 'className' => 'text-center'],
                ['data' => 'nilai_dpa', 'name' => 'nilai_dpa', 'className' => 'text-center'],
                ['data' => 'nilai_akhir', 'name' => 'nilai_akhir', 'className' => 'text-center'],
            ]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>ID FREEFORM</th>
                            <th>Nim</th>
                            <th>Nama</th>
                            <th>Nama Perguruan Tinggi</th>
                            <th>Prodi.</th>
                            <th>Free Form</th>
                            <th>Nilai DPL</th>
                            <th>Nilai DPA</th>
                            <th>Nilai Akhir</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>

<x-button.export url="{{ url('admfreeform/export') }}" />
