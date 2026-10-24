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
        var order = $table.data('order');

        // Opt-in selection column (data-select-key); handled by bulk-actions.js
        var selectKey = $table.data('select-key');
        if (selectKey) {
            $table.find('thead tr').first().prepend(
                '<th width="1" class="text-center"><input type="checkbox" class="form-check-input" data-bulk-all aria-label="Pilih semua baris di halaman ini"></th>'
            );
            columns.unshift({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    if (type !== 'display') return '';
                    var value = $('<div>').text(row[selectKey] == null ? '' : String(row[selectKey])).html().replace(/"/g, '&quot;');
                    return '<input type="checkbox" class="form-check-input" data-bulk-row value="' + value + '" aria-label="Pilih baris">';
                },
            });
            // Shift order indexes past the new first column
            order = (order || []).map(function (o) { return [o[0] + 1, o[1]]; });
            // DataTables merges $table.data() over init options; keep the cache in sync
            $table.data('columns', columns).data('order', order);
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500, // debounce search requests
            ajax: $table.data('ajax'),
            columns: columns,
            order: order,
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