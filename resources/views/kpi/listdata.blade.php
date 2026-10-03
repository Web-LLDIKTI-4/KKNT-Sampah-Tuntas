
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            table-class="table table-sm"
            thead-class=""
            ajax="{{ route('kpi.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'id_kpi', 'name' => 'id_kpi', 'visible' => false],
                ['data' => 'nama_kpi', 'name' => 'nama_kpi'],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
            ]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Id KPI </th>
                            <th>Nama KPI</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>

<x-button.export url="{{ route('kpi.export') }}" />
