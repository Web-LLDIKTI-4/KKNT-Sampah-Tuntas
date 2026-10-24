/*
 * Bulk action untuk tabel <x-table selectable> + <x-bulk-toolbar target="#id">.
 * - Pilihan per tabel (hanya halaman tampil), reset saat DataTables request ulang (preXhr).
 * - Edit  : modal #modalku (form [data-bulk-form]), ids[] disisipkan sebelum handler crud.js.
 * - Export: POST fetch -> blob. Hapus: PUT setelah konfirmasi.
 * Respons hasil: {success, toast, message, processed, skipped, failed, skipped_items, failed_items}
 */
(function ($) {
    'use strict';

    if (window.BulkActions) {
        return; // script ikut dimuat ulang tiap partial
    }

    var activeTarget = null;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function selected($table) {
        var set = $table.data('bulk-selected');
        if (!set) {
            set = new Set();
            $table.data('bulk-selected', set);
        }
        return set;
    }

    function toolbarFor($table) {
        return $('[data-bulk-toolbar]').filter(function () {
            return $($(this).data('target')).is($table);
        });
    }

    // Ids terpilih sesuai urutan baris tampil
    function orderedIds($table) {
        var set = selected($table);
        var key = $table.data('select-key');
        var ids = [];
        if (!$.fn.DataTable.isDataTable($table)) {
            return ids;
        }
        $table.DataTable().rows({ page: 'current' }).data().each(function (row) {
            var id = row[key] == null ? '' : String(row[key]);
            if (set.has(id)) ids.push(id);
        });
        return ids;
    }

    function sync($table) {
        var count = selected($table).size;
        var $rows = $table.find('tbody input[data-bulk-row]');
        var checked = $rows.filter(':checked').length;
        var $all = $table.find('thead input[data-bulk-all]');
        $all.prop('checked', $rows.length > 0 && checked === $rows.length)
            .prop('indeterminate', checked > 0 && checked < $rows.length);
        $rows.each(function () {
            $(this).closest('tr').toggleClass('table-active', this.checked);
        });

        var $bar = toolbarFor($table);
        $bar.toggleClass('is-active border-primary', count > 0);
        $bar.find('[data-bulk-count]').text(count + ' dipilih')
            .toggleClass('bg-primary', count > 0).toggleClass('bg-label-secondary', count === 0);
        $bar.find('[data-bulk-action]').prop('disabled', count === 0);
        $bar.find('[data-bulk-action="clear"]').prop('hidden', count === 0);
    }

    function reset($table) {
        selected($table).clear();
        $table.find('tbody input[data-bulk-row], thead input[data-bulk-all]').prop('checked', false);
        sync($table);
    }

    function tableFromToolbar(el) {
        return $($(el).closest('[data-bulk-toolbar]').data('target'));
    }

    function toast(type, message) {
        var fn = ['success', 'warning', 'error', 'info'].indexOf(type) >= 0 ? type : 'success';
        toastr[fn](message);
    }

    // Ringkasan hasil di bawah toolbar; semua teks lewat .text() (escape)
    function showSummary($table, ret) {
        var $bar = toolbarFor($table);
        var $box = $bar.nextAll('[data-bulk-result]').first();
        if (!$box.length || ret.processed === undefined) return;

        var hasIssue = (ret.skipped || 0) + (ret.failed || 0) > 0;
        var level = ret.failed > 0 && !ret.processed ? 'danger' : (hasIssue ? 'warning' : 'success');
        var $alert = $('<div class="alert alert-dismissible py-2 mb-3" role="status"></div>').addClass('alert-' + level);
        var $stats = $('<div class="d-flex flex-wrap gap-3 small fw-medium"></div>');
        $('<span><i class="ri-checkbox-circle-line me-1"></i></span>').append(document.createTextNode((ret.processed || 0) + ' berhasil')).appendTo($stats);
        $('<span><i class="ri-skip-forward-line me-1"></i></span>').append(document.createTextNode((ret.skipped || 0) + ' dilewati')).appendTo($stats);
        $('<span><i class="ri-error-warning-line me-1"></i></span>').append(document.createTextNode((ret.failed || 0) + ' gagal')).appendTo($stats);
        $alert.append($stats);

        var items = [];
        $.each(ret.skipped_items || [], function (i, it) {
            items.push((it.label || it.id) + ' — dilewati' + (it.reason ? ': ' + it.reason : ''));
        });
        $.each(ret.failed_items || [], function (i, it) {
            items.push((it.label || it.id) + ' — gagal');
        });
        if (items.length) {
            var $details = $('<details class="mt-2 small"><summary>Lihat rincian</summary></details>');
            var $list = $('<ul class="mb-0 mt-1 ps-3"></ul>');
            $.each(items, function (i, text) { $('<li></li>').text(text).appendTo($list); });
            $alert.append($details.append($list));
        }
        $alert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>');
        $box.empty().append($alert);
    }

    function handleResult($table, ret) {
        if (!ret || !ret.success) {
            toastr.warning((ret && ret.message) || 'Aksi tidak dapat diproses.');
            return;
        }
        toast(ret.toast, ret.message);
        showSummary($table, ret);
        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().ajax.reload(null, false); // preXhr ikut mereset pilihan
        }
    }

    function errorMessage(status, ret) {
        if (status === 419) return 'Sesi berakhir, silakan muat ulang halaman.';
        if (status === 429) return (ret && ret.message) || 'Terlalu banyak permintaan, coba lagi nanti.';
        return (ret && ret.message) || 'Terjadi kesalahan pada server.';
    }

    // Seleksi
    $(document).on('change', 'table[data-select-key] tbody input[data-bulk-row]', function () {
        var $table = $(this).closest('table');
        var set = selected($table);
        this.checked ? set.add(this.value) : set.delete(this.value);
        sync($table);
    });

    $(document).on('change', 'table[data-select-key] thead input[data-bulk-all]', function () {
        var $table = $(this).closest('table');
        var set = selected($table);
        var on = this.checked;
        $table.find('tbody input[data-bulk-row]').each(function () {
            this.checked = on;
            on ? set.add(this.value) : set.delete(this.value);
        });
        sync($table);
    });

    // Ganti halaman/filter/pencarian/urutan/reload -> reset; draw -> sinkron header
    $(document).on('preXhr.dt', 'table[data-select-key]', function (e) {
        if (e.target === this) reset($(this));
    }).on('draw.dt', 'table[data-select-key]', function (e) {
        if (e.target === this) sync($(this));
    });

    $(document).on('click', '[data-bulk-action="clear"]', function () {
        reset(tableFromToolbar(this));
    });

    // Edit massal: tombol .modalButton (layouts/app) memuat form; catat tabel aktif
    $(document).on('click', '[data-bulk-action="edit"]', function () {
        activeTarget = $(this).closest('[data-bulk-toolbar]').data('target');
    });

    function fillForm($form) {
        var $table = $(activeTarget);
        var ids = orderedIds($table);
        var $holder = $form.find('[data-bulk-ids]');
        $holder.empty();
        $.each(ids, function (i, id) {
            $('<input type="hidden" name="ids[]">').val(id).appendTo($holder);
        });
        $form.find('[data-bulk-form-count]').text(ids.length);
        if (activeTarget) $form.attr('data-reload', activeTarget);
        return ids.length;
    }

    // Terpasang di #modalku agar jalan sebelum handler submit delegasi crud.js (document)
    $(function () {
        $('#modalku').on('submit', 'form[data-bulk-form]', function (e) {
            if (!fillForm($(this))) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $('#modalku').modal('hide');
                toastr.warning('Tidak ada data terpilih.');
            }
        }).on('ajax-form:saved', 'form[data-bulk-form]', function (e, ret) {
            $('#modalku').modal('hide');
            showSummary($(activeTarget), ret);
        });
    });

    // Export terpilih
    $(document).on('click', '[data-bulk-action="export"]', function () {
        var btn = this;
        var $table = tableFromToolbar(btn);
        var ids = orderedIds($table);
        if (!ids.length) return;

        var body = new URLSearchParams();
        $.each(ids, function (i, id) { body.append('ids[]', id); });
        btnLoading(btn, true, 'Menyiapkan...');

        fetch($(btn).closest('[data-bulk-toolbar]').data('export-url'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: body,
        }).then(function (res) {
            var type = res.headers.get('Content-Type') || '';
            if (!res.ok || type.indexOf('application/json') >= 0) {
                return res.json().catch(function () { return {}; }).then(function (ret) {
                    toastr.error(errorMessage(res.status, ret));
                });
            }
            var disposition = res.headers.get('Content-Disposition') || '';
            var match = /filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i.exec(disposition);
            var name = match ? decodeURIComponent(match[1] || match[2]) : 'data_terpilih.xlsx';
            return res.blob().then(function (blob) {
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = name;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                toastr.success(ids.length + ' data terpilih diekspor.');
                reset($table);
            });
        }).catch(function () {
            toastr.error('Gagal mengunduh file export.');
        }).finally(function () {
            btnLoading(btn, false);
            sync($table);
        });
    });

    // Hapus terpilih
    $(document).on('click', '[data-bulk-action="delete"]', function () {
        var btn = this;
        var $table = tableFromToolbar(btn);
        var ids = orderedIds($table);
        if (!ids.length) return;
        if (!confirm('Hapus ' + ids.length + ' data terpilih beserta SEMUA data terkait? Data dihapus permanen dan tidak dapat dibatalkan.')) {
            return;
        }

        $.ajax({
            url: $(btn).closest('[data-bulk-toolbar]').data('delete-url'),
            method: 'PUT',
            data: { ids: ids },
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            beforeSend: function () { btnLoading(btn, true, 'Menghapus...'); },
            complete: function () { btnLoading(btn, false); sync($table); }, // btnLoading re-enables
            success: function (ret) { handleResult($table, ret); },
            error: function (xhr) { toastr.error(errorMessage(xhr.status, xhr.responseJSON)); }
        });
    });

    window.BulkActions = { fillForm: fillForm };
})(jQuery);
