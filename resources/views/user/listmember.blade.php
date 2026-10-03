<x-bulk-select-table
    :ajax="route('user.getdatamember.listdataserver')"
    :action="url('user/insert')"
    :columns="[
        ['data' => 'nim', 'label' => 'Nim'],
        ['data' => 'nama', 'label' => 'Nama'],
        ['data' => 'email', 'label' => 'Email'],
        ['data' => 'nm_lemb', 'label' => 'Perguruan Tinggi'],
        ['data' => 'location_program', 'label' => 'Lokasi Program KKN'],
    ]"
    :pt-options="$ptOptions"
    :lokasi-options="$lokasiOptions"
    submit-label="Tambah Pengguna"
/>
