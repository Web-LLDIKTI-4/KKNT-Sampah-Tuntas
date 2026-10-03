<x-bulk-select-table
    :ajax="route('user.adduser.listdataserver')"
    :action="url('user/insertuser')"
    :columns="[
        ['data' => 'nidn', 'label' => 'NIDN'],
        ['data' => 'nama', 'label' => 'Nama'],
        ['data' => 'prodi', 'label' => 'Program Studi'],
        ['data' => 'nm_lemb', 'label' => 'Perguruan Tinggi'],
        ['data' => 'location_program', 'label' => 'Lokasi Program KKN'],
    ]"
    :pt-options="$ptOptions"
    :lokasi-options="$lokasiOptions"
    submit-label="Tambah Pengguna"
/>
