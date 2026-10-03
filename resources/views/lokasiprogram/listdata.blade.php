<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('lokasiprogram.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id', 'name' => 'id', 'visible' => false],
                ['data' => 'gambar', 'name' => 'gambar', 'orderable' => false, 'searchable' => false],
                ['data' => 'nama_lokasi', 'name' => 'nama_lokasi'],
                ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
            ]"
            :wrap="[3]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Id</th>
                            <th>Gambar</th>
                            <th>Nama Lokasi</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
