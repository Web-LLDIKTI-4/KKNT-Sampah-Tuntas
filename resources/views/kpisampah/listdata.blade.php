<div class="row">
    <div class="col-12">
        <x-table
            thead-class=""
            ajax="{{ route('kpisampah.listdataserver') }}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'bulan', 'name' => 'bulan'],
                ['data' => 'kecamatan', 'name' => 'kecamatan'],
                ['data' => 'kelurahan', 'name' => 'kelurahan'],
                ['data' => 'jml_rw_kbs', 'name' => 'jml_rw_kbs', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'jml_rw_non_kbs', 'name' => 'jml_rw_non_kbs', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'jml_rumah', 'name' => 'jml_rumah', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'jml_rumah_memilah', 'name' => 'jml_rumah_memilah', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'persen_ketaatan', 'name' => 'persen_ketaatan', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'timbulan', 'name' => 'timbulan', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'pengurangan_organik', 'name' => 'pengurangan_organik', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'pengurangan_anorganik', 'name' => 'pengurangan_anorganik', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'pengurangan', 'name' => 'pengurangan', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'residu', 'name' => 'residu', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'persen_pengurangan', 'name' => 'persen_pengurangan', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'jml_bank_sampah', 'name' => 'jml_bank_sampah', 'className' => 'text-end', 'searchable' => false],
                ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false, 'visible' => (auth()->user()->akses === 'pjdesa')],
            ]"
            :scroll-x="true"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Bulan</th>
                            <th>Kecamatan</th>
                            <th>Kelurahan</th>
                            <th>RW KBS Dampingan DLH</th>
                            <th>RW Non-KBS</th>
                            <th>Rumah Keseluruhan</th>
                            <th>Rumah Memilah</th>
                            <th>Ketaatan Pemilah</th>
                            <th>Timbulan (kg/bulan)</th>
                            <th>Pengurangan Organik</th>
                            <th>Pengurangan Anorganik</th>
                            <th>Pengurangan</th>
                            <th>Residu (kg)</th>
                            <th>Pengurangan Sampah</th>
                            <th>Bank Sampah</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
