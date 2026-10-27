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
        // Opt-in data-export-state: send q/order + table filters (all rows) instead of full ajax params
        if ($link.is('[data-export-state]')) {
            $link.attr('href', exportStateUrl(base, $table));
            return;
        }
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

    function exportStateUrl(base, $table) {
        var url = new URL(base, window.location.origin);
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) return url.toString();
        var api = $table.DataTable();
        var order = api.order()[0];
        var orderMeta = order ? api.settings()[0].aoColumns[order[0]] : null;
        // Only orderable columns with a data name (skip DT_RowIndex / select column)
        var orderCol = orderMeta && orderMeta.bSortable ? api.column(order[0]).dataSrc() : null;
        if (typeof orderCol !== 'string' || orderCol === '') orderCol = null;
        var params = {
            q: String(api.search() || '').trim().slice(0, 100),
            order_col: orderCol || '',
            order_dir: orderCol ? (order[1] === 'desc' ? 'desc' : 'asc') : '',
        };
        // Carry filters baked into the ajax URL (e.g. user role/kodept) since the button lives outside the partial
        new URL(api.ajax.url(), window.location.origin).searchParams.forEach(function (value, key) {
            if (!(key in params)) params[key] = value;
        });
        tableFilters($table[0].id).each(function () {
            params[$(this).attr('data-table-filter')] = $(this).val();
        });
        $.each(params, function (key, value) {
            url.searchParams.delete(key);
            if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, value);
        });
        return url.toString();
    }

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

    // Render: null/empty -> red x icon (label as title), otherwise escaped text
    $.fn.dataTable.render.emptyIcon = function (label) {
        return function (data, type) {
            var empty = data === null || data === undefined || data === '';
            if (type !== 'display') return empty ? '' : data;
            return empty ? statusIcon(false, label) : escapeText(data);
        };
    };

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

    // Row values arrive HTML-escaped by yajra; decode only when needed (fast path for ids)
    function decodeCell(value) {
        var text = value === null || value === undefined ? '' : String(value);
        if (text.indexOf('&') === -1) return text;
        var decoder = document.createElement('textarea');
        decoder.innerHTML = text;
        return decoder.value;
    }

    function escapeAttr(value) {
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // by = {field, map}: map[row[field]] overrides the fallback
    function pickBy(fallback, by, row) {
        if (!by) return fallback;
        var key = decodeCell(row[by.field]);
        return Object.prototype.hasOwnProperty.call(by.map, key) ? by.map[key] : fallback;
    }

    // Render (row-based): edit/delete buttons, same markup as <x-action-data>; opts {idField, edit (URL prefix), editBy, delete, confirm, confirmBy}
    $.fn.dataTable.render.actions = function (opts) {
        var idField = opts.idField || 'id';
        return function (data, type, row) {
            if (type !== 'display') return '';
            var id = decodeCell(row[idField]);
            var edit = pickBy(opts.edit, opts.editBy, row);
            var html = '<div class="d-flex justify-content-center gap-2">';
            if (edit) {
                html += '<a href="#modalku" data-bs-toggle="modal" data-src="' + escapeAttr(edit + encodeURIComponent(id))
                    + '" class="modalButton btn-action-edit" title="Ubah Data"><i class="ri-edit-box-line"></i></a>';
            }
            if (opts['delete'] && id !== '') {
                html += '<button type="button" class="btn-action-delete btn-delete" title="Hapus Data" data-url="' + escapeAttr(opts['delete'])
                    + '" data-id-field="' + escapeAttr(idField) + '" data-id-value="' + escapeAttr(id)
                    + '" data-confirm="' + escapeAttr(pickBy(opts.confirm || 'Anda yakin ingin menghapus data ini?', opts.confirmBy, row))
                    + '"><i class="ri-delete-bin-3-line"></i></button>';
            }
            return html + '</div>';
        };
    };
})(jQuery);
