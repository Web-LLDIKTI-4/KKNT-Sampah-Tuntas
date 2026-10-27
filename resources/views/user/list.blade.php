<x-bulk-toolbar target="#dataTable-user" :edit-url="url('user/bulk-edit')" :export-url="url('user/export-selected')" :delete-url="url('user/bulk-destroy')" />
<div class="table-responsive">
<x-table
    id="dataTable-user"
    :selectable="auth()->user()?->role === 'admin'"
    select-key="id"
    :page-lengths="[10, 25, 50, 100, 500, -1]"
    :ajax="route('user.listdataserver', $filters)"
    :columns="[
        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
        ['data' => 'email', 'name' => 'email'],
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'id_number', 'name' => 'id_number', 'className' => 'text-center'],
        ['data' => 'pt_name', 'name' => 'pt_name'],
        ['data' => 'role', 'name' => 'role'],
        ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false, 'actions' => [
            'edit' => url('user/edit').'/',
            'editBy' => ['field' => 'role', 'map' => ['kepala' => url('user/edituserkepala').'/', 'pemda' => url('user/edituserkepala').'/', 'pt' => url('user/edituserpt').'/']],
            'delete' => url('user/destroy'),
            'confirm' => 'Hapus user ini beserta SEMUA datanya? Tindakan ini tidak dapat dibatalkan.',
            'confirmBy' => ['field' => 'akses', 'map' => ['pjdesa' => 'User ini ketua kelompok. Evaluasi Pendataan Sampah dan data pengurangan sampah kelompok/desa ikut terhapus. Lanjutkan? Tindakan ini tidak dapat dibatalkan.']],
        ]],
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
