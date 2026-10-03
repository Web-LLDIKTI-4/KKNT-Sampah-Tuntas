<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            table-class="table table-bordered user_datatable"
            thead-class=""
            ajax="{{ route('pttugasakhir.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'nim', 'name' => 'nim', 'className' => 'text-center'],
                ['data' => 'nama', 'name' => 'nama'],
                ['data' => 'tugas_akhir', 'name' => 'tugas_akhir'],
            ]"
        >
            <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Laporan</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
