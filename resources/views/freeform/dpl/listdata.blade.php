
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            table-class="table table-bordered user_datatable"
            thead-class=""
            ajax="{{ route('dplfreeform.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_freeform', 'name' => 'id_freeform', 'visible' => false],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false, 'visible' => false],
                ['data' => 'nim', 'name' => 'nim'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'prodi', 'name' => 'prodi'],
                ['data' => 'freeform', 'name' => 'freeform'],
                ['data' => 'nilai_dpl', 'name' => 'nilai_dpl', 'className' => 'text-center'],
                ['data' => 'nilai_dpa', 'name' => 'nilai_dpa', 'className' => 'text-center'],
            ]"
        >
            <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>ID FREEFORM</th>
                            <th>Aksi</th>
                            <th>Nim</th>
                            <th>Nama</th>
                            <th>Perguruan Tinggi</th>
                            <th>Prodi.</th>
                            <th>Free Form</th>
                            <th>Nilai DPL</th>
                            <th>Nilai DPA</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
