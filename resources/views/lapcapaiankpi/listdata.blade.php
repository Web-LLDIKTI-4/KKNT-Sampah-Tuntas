<div class="row">
    <div class="col-12 table-responsive">
        <x-table
            thead-class=""
            ajax="{{ route('lapcapaiankpi.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'lokasi', 'name' => 'lokasi'],
                ['data' => 'pjdesa', 'name' => 'pjdesa'],
                ['data' => 'nama_kpi', 'name' => 'nama_kpi'],
                ['data' => 'permasalahan', 'name' => 'permasalahan'],
                ['data' => 'solusi', 'name' => 'solusi'],
                ['data' => 'kendala', 'name' => 'kendala'],
                ['data' => 'status_capaian', 'name' => 'status_capaian', 'className' => 'text-center'],
                ['data' => 'tautan', 'name' => 'tautan'],
            ]"
            :wrap-text="[4, 5, 6]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            {{-- <th>Id kpicapaian</th> --}}
                            <th>Lokasi Kegiatan</th>
                            <th>Ketua Kelompok</th>
                            <th>Nama KPI</th>
                            <th>Permasalahan</th>
                            <th>Solusi</th>
                            <th>Kebutuhan Dukungan</th>
                            <th>Tindak Lanjut</th>
                            <th>Tautan</th>
                            {{-- <th width="1">Aksi</th> --}}
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>

<x-button.export url="{{ url('lapcapaiankpi/export') }}" />
