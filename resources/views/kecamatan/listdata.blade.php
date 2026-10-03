<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('kecamatan.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_kecamatan', 'name' => 'id_kecamatan', 'visible' => false],
                ['data' => 'kecamatan', 'name' => 'kecamatan'],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
            ]"
            :wrap="[3]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Id Kecamatan</th>
                            <th>Nama Kecamatan</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
