$(function () {
    // Delegated handler for .btn-delete (rendered per-row by DataTables via PHP)
    $(document).on('click', '.btn-delete', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        var idField = $btn.data('id-field');
        var idValue = $btn.data('id-value');
        var confirmMsg = $btn.data('confirm') || 'Anda yakin ingin menghapus data ini?';

        if (!confirm(confirmMsg)) return;

        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var payload = {};
        payload[idField] = idValue;

        $.ajax({
            url: url,
            method: 'PUT',
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (ret) {
                if (ret.success) {
                    toastr.success(ret.message);
                    $btn.closest('tr').fadeOut(300, function () {
                        var dt = $.fn.DataTable.isDataTable('table[data-datatable]')
                            ? $('table[data-datatable]').DataTable()
                            : null;
                        if (dt) { dt.row($btn.closest('tr')).remove().draw(false); }
                        else { $(this).remove(); }
                    });
                } else {
                    toastr.warning(ret.message);
                }
            },
            error: function (xhr) {
                console.error(xhr.status, xhr.responseText);
                toastr.error('Terjadi kesalahan saat menghapus data');
            }
        });
    });

    $('table[data-datatable]').each(function () {
        var $table = $(this);

        if ($.fn.DataTable.isDataTable($table)) {
            return; // hindari inisialisasi ganda
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: $table.data('ajax'),
            columns: $table.data('columns'),
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