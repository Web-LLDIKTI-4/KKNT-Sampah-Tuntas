// Global DataTables handling: inline errors, partial loader, export with table params
(function ($) {
    // Handle errors via error.dt below instead of alert()
    $.fn.dataTable.ext.errMode = 'none';

    // error.dt bubbles from every table (DataTables triggers on body when detached)
    $(document).on('error.dt', function (e, settings, techNote) {
        var $table = $(settings.nTable);
        var xhr = settings.jqXHR;
        if (xhr && (xhr.status === 401 || xhr.status === 419)) {
            location.reload(); // session expired -> login
            return;
        }
        var message = (techNote === 1 || techNote === 7) ? 'Gagal memuat data, coba lagi.' : 'Terjadi kesalahan saat menampilkan data.';
        if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }
        var $error = $table.data('dt-error');
        if (!$error) {
            $error = $('<div class="alert alert-danger py-2 mb-3" role="alert"></div>');
            $table.data('dt-error', $error);
        }
        var $wrapper = $table.closest('.dataTables_wrapper');
        $error.text(message).insertBefore($wrapper.length ? $wrapper : $table);
    }).on('xhr.dt', function (e, settings, json) {
        var $error = $(settings.nTable).data('dt-error');
        if (json && $error) $error.detach(); // clear after a successful reload
    });

    function partialErrorMessages(xhr) {
        var messages = ['Gagal memuat data, coba lagi.'];
        if (xhr.status === 422) {
            try {
                var json = JSON.parse(xhr.responseText);
                messages = json.errors ? $.map(json.errors, function (v) { return v; }) : [json.message];
            } catch (err) {}
        }
        return messages;
    }

    // Load list HTML into target: abort stale request, destroy old DataTables, keep search keyword
    window.loadPartial = function (target, url, opts) {
        var $content = $(target);
        opts = $.extend({ data: {}, loadingText: 'loading data...', keepSearch: true }, opts);

        // Reverse DOM order: nested (child) tables are destroyed before their parent
        var tables = $content.find('table').filter(function () {
            return $.fn.DataTable.isDataTable(this);
        }).get().reverse();
        if (tables.length && opts.keepSearch) {
            $content.data('dt-keyword', $(tables[tables.length - 1]).DataTable().search());
        }
        $.each(tables, function (i, table) { $(table).DataTable().destroy(); });

        var prev = $content.data('partial-xhr');
        if (prev) prev.abort(); // drop stale request on rapid filter change

        $content.text(opts.loadingText);
        var xhr = $.ajax({ url: url, data: opts.data, dataType: 'html' })
            .done(function (html) {
                // Restore keyword before the first server request
                var keyword = opts.keepSearch ? $content.data('dt-keyword') : '';
                $content.off('preInit.partial');
                if (keyword) {
                    $content.one('preInit.dt.partial', function (e, settings) {
                        new $.fn.dataTable.Api(settings).search(keyword);
                    });
                }
                $content.html(html);
            })
            .fail(function (xhr, status) {
                if (status === 'abort') return;
                if (xhr.status === 401 || xhr.status === 419) {
                    location.reload(); // session expired -> login
                    return;
                }
                var $alert = $('<div class="alert alert-danger py-2 mb-0" role="alert"></div>');
                $.each(partialErrorMessages(xhr), function (i, msg) { $('<div>').text(msg).appendTo($alert); });
                $content.empty().append($alert);
            })
            .always(function () {
                if ($content.data('partial-xhr') === xhr) $content.removeData('partial-xhr');
            });
        $content.data('partial-xhr', xhr);
        return xhr;
    };

    // Export link: append current DataTables params (columns/search/order) to the export URL
    $(document).on('click', '[data-export-table]', function () {
        var $link = $(this);
        var base = $link.data('export-base');
        if (base === undefined) {
            base = $link.attr('href');
            $link.data('export-base', base);
        }
        var $table = $($link.attr('data-export-table'));
        var params = $table.length && $.fn.DataTable.isDataTable($table) ? $table.DataTable().ajax.params() : null;
        if (!params) {
            $link.attr('href', base);
            return;
        }
        params = $.extend({}, params);
        delete params.draw;
        delete params.start;
        delete params.length;
        $link.attr('href', base + (base.indexOf('?') === -1 ? '?' : '&') + $.param(params));
    });

    // Opt-in filter: <select data-table-filter="param" data-table-target="#tableId"> is sent as ajax param
    function tableFilters(tableId) {
        return tableId ? $('[data-table-filter]').filter(function () {
            return $(this).attr('data-table-target') === '#' + tableId;
        }) : $();
    }
    $(document).on('preXhr.dt', function (e, settings, data) {
        tableFilters(settings.nTable.id).each(function () {
            data[$(this).attr('data-table-filter')] = $(this).val();
        });
    }).on('change', '[data-table-filter]', function () {
        var $table = $($(this).attr('data-table-target'));
        if ($table.length && $.fn.DataTable.isDataTable($table)) $table.DataTable().draw();
    });

    // Decode first (yajra escapes by default) then escape, so output is safe without double-escaping
    function escapeText(value) {
        var decoder = document.createElement('textarea');
        decoder.innerHTML = String(value);
        return $('<div>').text(decoder.value).html();
    }

    // Render: null/empty -> red badge, otherwise escaped text
    $.fn.dataTable.render.emptyBadge = function (label) {
        return function (data, type) {
            var empty = data === null || data === undefined || data === '';
            if (type !== 'display') return empty ? '' : data;
            return empty ? '<span class="badge bg-danger">' + escapeText(label) + '</span>' : escapeText(data);
        };
    };

    // Shared check / x icon (title attr escaped by jQuery)
    function statusIcon(ok, label) {
        return $('<i>', {
            'class': ok ? 'ri-checkbox-circle-fill text-success' : 'ri-close-circle-fill text-danger',
            title: label,
            'aria-label': label,
        }).prop('outerHTML');
    }

    // Render (row-based): kecamatan & desa filled -> green check, else red x; detail in title (attr escaped by jQuery)
    $.fn.dataTable.render.lokasiStatus = function () {
        var decode = function (value) {
            var decoder = document.createElement('textarea');
            decoder.innerHTML = value === null || value === undefined ? '' : String(value);
            return decoder.value;
        };
        return function (data, type, row) {
            if (type !== 'display') return data;
            var kecamatan = decode(row.nama_kecamatan);
            var desa = decode(row.nama_desa);
            var missing = [];
            if (kecamatan === '') missing.push('Kecamatan');
            if (desa === '') missing.push('Kelurahan/Desa');
            var label = missing.length ? missing.join(' dan ') + ' belum diisi' : 'Kecamatan: ' + kecamatan + ', Desa: ' + desa;
            return statusIcon(!missing.length, label);
        };
    };

    // Render: truthy -> green check, else red x; labels used as title/aria-label
    $.fn.dataTable.render.boolIcon = function (trueLabel, falseLabel) {
        return function (data, type) {
            if (type !== 'display') return data;
            var on = data === true || data === 1 || data === '1';
            return statusIcon(on, on ? trueLabel : falseLabel);
        };
    };
})(jQuery);
