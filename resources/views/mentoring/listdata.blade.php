
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('dplmentoring.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_mentoring', 'name' => 'id_mentoring', 'visible' => false],
                ['data' => 'nim', 'name' => 'nim', 'className' => 'text-center'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'prodi', 'name' => 'prodi'],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'rekapnilai', 'name' => 'rekapnilai', 'className' => 'text-center'],
                ['data' => 'tugasakhir', 'name' => 'tugasakhir', 'className' => 'text-center'],
            ]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>ID MENTORING</th>
                            <th>Nim</th>
                            <th>Nama</th>
                            <th>Perguruan Tinggi</th>
                            <th>Prodi</th>
                            <th>Aksi</th>
                            <th>Nilai Log Bulanan</th>
                            {{-- <th>Nilai & Free Form</th> --}}
                            <th>Tugas akhir</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
