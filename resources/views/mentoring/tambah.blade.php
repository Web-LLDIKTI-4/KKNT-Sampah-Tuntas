<x-bulk-select-table
    :ajax="route('dplmentoring.tambah.listdataserver')"
    :action="url('dplmentoring/insert')"
    :columns="[
        ['data' => 'nim', 'label' => 'Nim', 'className' => 'text-center'],
        ['data' => 'nama', 'label' => 'Nama'],
        ['data' => 'nm_lemb', 'label' => 'Nama Perguruan Tinggi'],
        ['data' => 'location_program', 'label' => 'Lokasi Program KKN', 'className' => 'text-center'],
    ]"
    :pt-options="$ptOptions"
    :lokasi-options="$lokasiOptions"
    submit-label="Tambah Mahasiswa"
    after-success="#dataTable"
/>
