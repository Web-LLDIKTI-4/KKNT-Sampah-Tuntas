<div class="table-responsive">
<x-table
    id="dataTable-user"
    :ajax="route('user.listdataserver', $filters)"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'email', 'name' => 'email'],
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'id_number', 'name' => 'id_number', 'className' => 'text-center'],
        ['data' => 'pt_name', 'name' => 'pt_name'],
        ['data' => 'role', 'name' => 'role'],
        ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
    ]"
>
    <x-slot:thead>
        <tr>
            <th width="1">No</th>
            <th>Nama Pengguna</th>
            <th>Nama</th>
            <th>Nim/NIDN</th>
            <th>Perguruan Tinggi</th>
            <th>Peran</th>
            <th width="1">Aksi</th>
        </tr>
    </x-slot:thead>
</x-table>
</div>

<x-button.export id="btn-export-user" url="{{ route('user.export', $filters) }}" />
<script>
// Append current DataTable search keyword as `q` (instance may be recreated on filter change)
$('#btn-export-user').on('click', function () {
    var $table = $('#dataTable-user');
    var keyword = $.fn.DataTable.isDataTable($table) ? String($table.DataTable().search() || '').trim().slice(0, 100) : '';
    var url = new URL(this.href, window.location.origin);
    url.searchParams.delete('q');
    if (keyword) url.searchParams.set('q', keyword);
    this.href = url.toString();
});
</script>
