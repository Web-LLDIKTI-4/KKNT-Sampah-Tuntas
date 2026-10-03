<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            table-class="table table-bordered table-sms"
            thead-class=""
            ajax="{{ route('admlogharian.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'kodept', 'name' => 'kodept', 'className' => 'text-center'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'nim', 'name' => 'nim'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'jumlah_log', 'name' => 'jumlah_log', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false, 'visible' => true],
            ]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Kode Perguruan Tinggi</th>
                            <th>Nama Perguruan Tinggi</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Jumlah Hari</th>
                            <th>Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>

<x-button.export url="{{ url('admlogharian/export') }}">Export Data</x-button.export>
