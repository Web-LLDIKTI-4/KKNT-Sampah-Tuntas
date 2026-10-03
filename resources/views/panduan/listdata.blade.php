@php($panduanLanguage = ['searchPlaceholder' => 'Cari judul atau nama file...', 'zeroRecords' => 'Belum ada panduan. Klik "Tambah Panduan" untuk mengunggah.'])
<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('panduan.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'judul', 'name' => 'judul'],
                ['data' => 'is_aktif', 'name' => 'is_aktif', 'searchable' => false],
                ['data' => 'file', 'name' => 'nama_file'],
                ['data' => 'created_at', 'name' => 'created_at', 'searchable' => false],
                ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
            ]"
            :order="[[4, 'desc']]"
            :language="$panduanLanguage"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Judul</th>
                            <th>Status</th>
                            <th>File</th>
                            <th>Diunggah</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
