<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('logkehadiran.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'tanggal', 'name' => 'tanggal', 'className' => 'text-center'],
                ['data' => 'status_kehadiran', 'name' => 'status_kehadiran', 'className' => 'text-center'],
                ['data' => 'waktu_masuk', 'name' => 'waktu_masuk', 'className' => 'text-center'],
                ['data' => 'coordinates_datang', 'name' => 'coordinates_datang', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'waktu_pulang', 'name' => 'waktu_pulang', 'className' => 'text-center'],
                ['data' => 'coordinates_pulang', 'name' => 'coordinates_pulang', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
            ]"
        >
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Status kehadiran</th>
                    <th>Jam Masuk</th>
                    <th>Lokasi Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Lokasi Pulang</th>
                </tr>
            </x-slot:thead>
        </x-table>
    </div>
</div>
<hr>

<x-button.export url="{{ url('logkehadiran/export') }}" />