<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('desaprofile.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_profile', 'name' => 'id_profile', 'visible' => false],
                ['data' => 'tahun', 'name' => 'tahun', 'className' => 'text-center'],
                ['data' => 'desa', 'name' => 'desa'],
                ['data' => 'potensi', 'name' => 'potensi'],
                ['data' => 'masalah', 'name' => 'masalah'],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
            ]"
            :wrap-text="[4, 5]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Id profile</th>
                            <th>Tahun</th>
                            <th>Nama Desa / Kelurahan</th>
                            <th>Potensi</th>
                            <th>Masalah</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
