// errMode & error.dt handled globally in datatables-global.js
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

        // Opt-in column key: emptyBadge (null -> red badge)
        var columns = ($table.data('columns') || []).map(function (col) {
            if (col.emptyBadge) col.render = $.fn.dataTable.render.emptyBadge(col.emptyBadge);
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