/*
 * Handler CRUD global.
 * - Form AJAX : <form data-ajax-form [data-reload="#dataTable"] | [data-reload-url data-reload-target]>
 *               [data-reload-page] (tombol submit id="btnSubmit_{formId}")
 *               [data-result-target="#el"] : respons HTML dimuat ke #el (form pemuat isian)
 * - Hapus     : .btn-delete (lihat <x-action-data>) data-url data-id-field data-id-value [data-confirm]
 * - Loading   : btnLoading(btn, true|false[, label]) — satu-satunya state loading tombol
 * Respons server: {success, message, errors?}
 */
(function ($) {
    'use strict';

    // Tombol ikon (tanpa teks) cukup spinner kecil agar ukurannya tidak berubah
    window.btnLoading = function (btn, loading, label) {
        var $btn = $(btn);
        if (!$btn.length) {
            return;
        }
        if (!loading) {
            if ($btn.data('btn-loading')) {
                $btn.html($btn.data('btn-html')).prop('disabled', false).removeData('btn-loading');
            }
            return;
        }
        if ($btn.data('btn-loading')) {
            return;
        }
        var iconOnly = $.trim($btn.text()) === '';
        $btn.data('btn-html', $btn.html()).data('btn-loading', true).prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                + (iconOnly ? '' : ' ' + (label || 'Loading...')));
    };

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function reloadTable(selector) {
        var $table = $(selector || '#dataTable');
        if ($table.length && $.fn.DataTable.isDataTable($table)) {
            $table.DataTable().ajax.reload(null, false);
        }
    }

    function clearFieldErrors($form) {
        $form.find('.errors-message').remove();
        $form.find('[id$="_error"]').text('');
    }

    function showFieldErrors($form, errors) {
        clearFieldErrors($form);
        $.each(errors, function (key, messages) {
            // Pakai placeholder <span id="{field}_error"> bila tersedia
            var $slot = $form.find('#' + key.replace(/\./g, '_') + '_error');
            if ($slot.length) {
                $slot.text(messages[0]);
                return;
            }
            var $field = $form.find('[name="' + key + '"], [name="' + key + '[]"]').last();
            if (!$field.length) {
                toastr.warning(messages[0]);
                return;
            }
            var $target = $field.hasClass('select2-hidden-accessible') ? $field.next('.select2') : $field;
            $('<span class="errors-message text-danger d-block"></span>').text(messages[0]).insertAfter($target);
        });
    }

    $(document).on('focus change', 'form[data-ajax-form] :input', function () {
        $(this).siblings('.errors-message').remove();
    });

    $(document).on('submit', 'form[data-ajax-form]', function (e) {
        e.preventDefault();
        var form = this;
        var $form = $(form);

        // Validasi sisi klien (atribut HTML5) sebelum dikirim
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Pakai attr: form.id tertimpa oleh <input name="id">
        var $btn = $('#btnSubmit_' + $form.attr('id'));
        var resultTarget = $form.data('result-target');
        var hasFile = $form.find('input[type="file"]').length > 0;

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: hasFile ? new FormData(form) : $form.serialize(),
            processData: !hasFile,
            contentType: hasFile ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            beforeSend: function () {
                btnLoading($btn, true);
            },
            complete: function () {
                btnLoading($btn, false);
            },
            success: function (ret) {
                if (resultTarget) {
                    $(resultTarget).html(ret);
                    return;
                }
                if (ret.success) {
                    // Server boleh menentukan jenis toast, mis. import yang sebagian barisnya gagal
                    var toastType = ['success', 'warning', 'error', 'info'].indexOf(ret.toast) >= 0 ? ret.toast : 'success';
                    toastr[toastType](ret.message);
                    clearFieldErrors($form);
                    if ($form.is('[data-reload-page]')) {
                        $('#modalku').modal('hide');
                        setTimeout(function () { window.location.reload(); }, 600);
                    } else if ($form.data('reload-url')) {
                        $($form.data('reload-target') || '#resultcontent').load($form.data('reload-url'));
                    } else {
                        reloadTable($form.data('reload'));
                    }
                    $form.trigger('ajax-form:saved', [ret]);
                    return;
                }
                toastr.warning(ret.message);
                if (ret.errors) {
                    showFieldErrors($form, ret.errors);
                }
            },
            error: function (xhr) {
                var ret = xhr.responseJSON || {};
                if (xhr.status === 419) {
                    toastr.error('Sesi berakhir, silakan muat ulang halaman.');
                } else if (xhr.status === 413) {
                    // 413 bisa datang dari web server (HTML, tanpa JSON) sebelum mencapai Laravel
                    toastr.error(ret.message || 'Ukuran file melebihi batas server. Kecilkan file lalu coba lagi.');
                } else {
                    toastr.error(ret.message || 'Terjadi kesalahan pada server.');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        var $btn = $(this);
        if (!confirm($btn.data('confirm') || 'Anda yakin ingin menghapus data ini?')) {
            return;
        }

        var payload = {};
        payload[$btn.data('id-field')] = $btn.data('id-value');

        $.ajax({
            url: $btn.data('url'),
            method: 'PUT',
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            success: function (ret) {
                if (!ret.success) {
                    toastr.warning(ret.message || ret.error);
                    return;
                }
                toastr.success(ret.message);
                var $table = $btn.closest('table');
                if ($table.length && $.fn.DataTable.isDataTable($table)) {
                    var dt = $table.DataTable();
                    // Tabel server-side di-reload, tabel client-side cukup hapus barisnya
                    if (dt.ajax.url()) {
                        dt.ajax.reload(null, false);
                    } else {
                        dt.row($btn.closest('tr')).remove().draw(false);
                    }
                } else {
                    $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                }
            },
            error: function (xhr) {
                var ret = xhr.responseJSON || {};
                toastr.error(ret.message || ret.error || 'Terjadi kesalahan saat menghapus data');
            }
        });
    });
})(jQuery);
