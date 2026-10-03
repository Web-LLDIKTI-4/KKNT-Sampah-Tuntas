$(function () {
    $('table[data-datatable]').each(function () {
        var $table = $(this);

        if ($.fn.DataTable.isDataTable($table)) {
            return; // hindari inisialisasi ganda
        }

        // Kolom (index) yang isinya dibungkus div.text-wrap; data-wrap-text juga membuang tag HTML
        var wrap = $table.data('wrap') || [];
        var wrapText = $table.data('wrap-text') || [];
        var columnDefs = [];
        if (wrap.length) {
            columnDefs.push({ targets: wrap, render: function (data, type) {
                return type === 'display' ? "<div class='text-wrap'>" + (data == null ? '' : data) + "</div>" : data;
            } });
        }
        // data-strip: buang tag HTML, tampilkan teks ter-escape tanpa pembungkus
        var strip = $table.data('strip') || [];
        if (strip.length) {
            columnDefs.push({ targets: strip, render: function (data, type) {
                return type === 'display' ? $('<div>').text($('<div>').html(data == null ? '' : data).text()).html() : data;
            } });
        }
        if (wrapText.length) {
            columnDefs.push({ targets: wrapText, render: function (data, type) {
                if (type !== 'display') return data;
                var text = $('<div>').html(data == null ? '' : data).text();
                return $('<div class="text-wrap">').text(text).prop('outerHTML');
            } });
        }

        var options = {
            processing: true,
            serverSide: true,
            ajax: $table.data('ajax'),
            columns: $table.data('columns'),
            order: $table.data('order'),
            searching: $table.data('searching'),
            lengthChange: $table.data('length-change'),
            language: $.extend({
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            }, $table.data('language') || {}),
            columnDefs: columnDefs,
        };
        if ($table.data('scroll-x')) {
            options.scrollX = true;
        }
        if ($table.data('page-length')) {
            options.pageLength = $table.data('page-length');
        }
        // data-on-draw: nama fungsi global yang dipanggil tiap tabel selesai digambar
        var onDraw = window[$table.data('on-draw')];
        if (typeof onDraw === 'function') {
            options.drawCallback = function (settings) { onDraw.call(this, settings); };
        }

        $table.DataTable(options);
    });
});