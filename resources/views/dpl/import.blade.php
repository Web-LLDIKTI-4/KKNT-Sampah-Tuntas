<x-import-form
    :action="url('dpl/prosesimport')"
    :template="url('assets/format_doc/format_dpl.xlsx')"
    label="DPL"
    :reload-url="url('dpl/listdata')"
/>
