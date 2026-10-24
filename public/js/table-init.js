// errMode & error.dt handled globally in datatables-global.js
$(function () {
    $('table[data-datatable]').each(function () {
        var $table = $(this);

        if ($.fn.DataTable.isDataTable($table)) {
            return; // hindari inisialisasi ganda
        }

        // Opt-in column keys: emptyBadge (null -> red badge), lokasiStatus (kecamatan+desa check / x icon), boolIcon ([trueLabel, falseLabel])
        var columns = ($table.data('columns') || []).map(function (col) {
            if (col.emptyBadge) col.render = $.fn.dataTable.render.emptyBadge(col.emptyBadge);
            if (col.lokasiStatus) col.render = $.fn.dataTable.render.lokasiStatus();
            if (col.boolIcon) col.render = $.fn.dataTable.render.boolIcon(col.boolIcon[0], col.boolIcon[1]);
            return col;
        });

        $table.DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500, // debounce search requests
            ajax: $table.data('ajax'),
            columns: columns,
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