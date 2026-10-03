
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            table-class="table table-bordered user_datatable"
            thead-class=""
            ajax="{{ route('dplkonversinilai.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false, 'visible' => (in_array(Auth::user()->role, ['dpl']))],
                ['data' => 'nim', 'name' => 'nim', 'className' => 'text-center'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'prodi', 'name' => 'prodi'],
                ['data' => 'matakuliah', 'name' => 'matakuliah'],
                ['data' => 'sks', 'name' => 'sks', 'className' => 'text-center'],
                ['data' => 'nilai_dpl', 'name' => 'nilai_dpl', 'className' => 'text-center'],
            ]"
        >
            <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th width="1">Aksi</th>
                            <th>Nim</th>
                            <th>Nama</th>
                            <th>Perguruan Tinggi</th>
                            <th>Prodi</th>
                            <th>Matakuliah</th>
                            <th>SKS</th>
                            <th>Nilai DPL</th>
                            {{-- <th>Nilai DPA</th> --}}
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
