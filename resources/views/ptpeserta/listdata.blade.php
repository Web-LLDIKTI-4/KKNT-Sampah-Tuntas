
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('ptpeserta.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'kodept', 'name' => 'kodept'],
                ['data' => 'nm_lemb', 'name' => 'nm_lemb'],
                ['data' => 'jumlah_mhs', 'name' => 'jumlah_mhs', 'searchable' => false],
            ]"
        >
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kode Perguruan Tinggi</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Jumlah Mahasiswa</th>
                </tr>
            </x-slot:thead>
        </x-table>
    </div>
</div>