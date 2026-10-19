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
})(jQuery);
