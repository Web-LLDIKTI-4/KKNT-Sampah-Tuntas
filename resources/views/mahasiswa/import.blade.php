<x-import-form
    :action="url('mahasiswa/prosesimport')"
    :template="url('assets/format_doc/format_mahasiswa.xlsx')"
    label="mahasiswa"
    :reload-url="url('mahasiswa/listdata')"
/>
