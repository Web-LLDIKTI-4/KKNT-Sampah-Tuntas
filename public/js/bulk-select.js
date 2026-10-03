/**
 * Tabel pilih massal (x-bulk-select-table): filter PT/lokasi wajib, centang per halaman.
 * Respons endpoint insert: {success} atau {error}.
 */
$(function () {
    $('table[data-bulk-select]').each(function () {
        var $table = $(this);
        if ($.fn.DataTable.isDataTable($table)) {
            return;
        }

        var id = $table.attr('id');
        var $filter = $('[data-bulk-filter="' + id + '"]');
        var $form = $('form[data-bulk-form="' + id + '"]');
        var $all = $table.find('[data-bulk-all]');
        var $count = $form.find('[data-bulk-count]');
        var $btn = $('#btnSubmit_' + $form.attr('id'));

        function updateCount() {
            var total = $table.find('tbody input[name="createuser[]"]').length;
            var checked = $table.find('tbody input[name="createuser[]"]:checked').length;
            $count.text(checked + ' dipilih');
            $all.prop('checked', total > 0 && checked === total);
            $all.prop('indeterminate', checked > 0 && checked < total);
        }

        var dt = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: $table.data('ajax'),
                data: function (d) {
                    $filter.find('[data-param]').each(function () {
                        d[$(this).data('param')] = $(this).val();
                    });
                },
            },
            columns: $table.data('columns'),
            order: [],
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                emptyTable: "Tidak ada data. Pilih Perguruan Tinggi atau Lokasi terlebih dahulu.",
                zeroRecords: "Tidak ada data yang cocok",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            // Baris baru tidak pernah tercentang → pilihan otomatis kosong tiap ganti halaman/filter/cari
            drawCallback: updateCount,
        });

        $filter.on('change', '[data-param]', function () {
            dt.ajax.reload();
        });

        $all.on('change', function () {
            $table.find('tbody input[name="createuser[]"]').prop('checked', this.checked);
            updateCount();
        });
        $table.on('change', 'tbody input[name="createuser[]"]', updateCount);

        $form.on('submit', function (e) {
            e.preventDefault();
            if (!$table.find('tbody input[name="createuser[]"]:checked').length) {
                toastr.warning('Centang minimal satu data di halaman ini.');
                return;
            }
            if ($btn.prop('disabled')) {
                return;
            }
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                beforeSend: function () { btnLoading($btn, true); },
                complete: function () { btnLoading($btn, false); },
                success: function (ret) {
                    if (!$.isEmptyObject(ret.error)) {
                        toastr.warning(ret.error);
                        return;
                    }
                    toastr.success(ret.success);
                    $('#modalku').modal('hide');
                    var after = $form.data('after-success');
                    if (after === 'page') {
                        setTimeout(function () { window.location.reload(); }, 600);
                    } else if ($.fn.DataTable.isDataTable(after)) {
                        $(after).DataTable().ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message);
                    toastr.error(msg || 'Terjadi kesalahan, silakan coba lagi.');
                },
            });
        });
    });
});
