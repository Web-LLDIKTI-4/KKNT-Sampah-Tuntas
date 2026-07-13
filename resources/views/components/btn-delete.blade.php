@props([
    'url',
    'idField' => 'id',
    'idValue',
    'confirm' => 'Anda yakin ingin menghapus data ini?',
    'icon' => 'ri-delete-bin-3-line',
    'class' => 'btn-action-delete',
])
<a href="javascript:void(0)" class="btn-delete-inline {{ $class }}" title="Hapus Data"
   data-url="{{ $url }}" data-id-field="{{ $idField }}" data-id-value="{{ $idValue }}" data-confirm="{{ $confirm }}">
    <i class="{{ $icon }}"></i>
</a>
@once
<script>
$(function () {
    $(document).on('click', '.btn-delete-inline', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        var idField = $btn.data('id-field');
        var idValue = $btn.data('id-value');
        var confirmMsg = $btn.data('confirm');

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
                    $btn.closest('tr').remove();
                } else {
                    toastr.warning(ret.message);
                }
            },
            error: function (xhr, status, error) {
                console.log(xhr.status + "\n" + xhr.responseText + "\n" + error);
                toastr.error('Terjadi kesalahan saat menghapus data');
            }
        });
    });
});
</script>
@endonce
