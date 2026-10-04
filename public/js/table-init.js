$(function () {
    $('table[data-datatable]').each(function () {
        var $table = $(this);

        if ($.fn.DataTable.isDataTable($table)) {
            return; // hindari inisialisasi ganda
        }

        // data-check: kolom boolean -> ikon centang, '-' bila false
        var check = $table.data('check') || [];
        var columnDefs = check.length ? [{ targets: check, render: function (data, type) {
            if (type !== 'display') return data;
            return (data === true || data === 1 || data === '1') ? '<i class="ri-check-line text-success" aria-label="Ya"></i>' : '-';
        } }] : [];

        $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: $table.data('ajax'),
            columns: $table.data('columns'),
            order: $table.data('order'),
            searching: $table.data('searching'),
            lengthChange: $table.data('length-change'),
            columnDefs: columnDefs,
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
        });
    });
});