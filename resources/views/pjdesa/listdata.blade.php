<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('pjdesa.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_pjdesa', 'name' => 'id_pjdesa', 'visible' => false],
                ['data' => 'kecamatan', 'name' => 'kecamatan'],
                ['data' => 'desa', 'name' => 'desa'],
                ['data' => 'pjdesa', 'name' => 'pjdesa'],
                ['data' => 'instansi', 'name' => 'instansi'],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
            ]"
            :wrap="[3]"
        >
            <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Id pjdesa</th>
                            <th>Nama Kecamatan</th>
                            <th>Nama Desa / Kelurahan</th>
                            <th>Ketua Kelompok</th>
                            <th>Instansi</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
