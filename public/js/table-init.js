$(function () {
    $('table[data-datatable]').each(function () {
        var $table = $(this);

        if ($.fn.DataTable.isDataTable($table)) {
            return; // hindari inisialisasi ganda
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: $table.data('ajax'),
            columns: $table.data('columns'),
            order: $table.data('order'),
            searching: $table.data('searching'),
            lengthChange: $table.data('length-change'),
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
        });
    });
});