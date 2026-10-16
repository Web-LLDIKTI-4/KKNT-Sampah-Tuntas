@props(['action', 'template', 'label', 'reloadUrl'])
{{-- Import Excel via AJAX (public/js/crud.js); baris gagal ditampilkan di modal --}}
<div class="alert alert-info">
    Gunakan <a href="{{ $template }}">template</a> ini untuk import data {{ $label }}
</div>

<form id="form-import" method="post" action="{{ $action }}" enctype="multipart/form-data" data-ajax-form data-reload-url="{{ $reloadUrl }}">
    @csrf
    @method('PUT')
    <input type="file" name="file" class="form-control form-control-sm" required accept=".xlsx,.xls,.csv">
    <div id="import-errors" class="alert alert-warning mt-3 mb-0 d-none">
        <ul class="mb-0 ps-3" style="max-height: 300px; overflow-y: auto;"></ul>
        <a href="#" class="import-errors-toggle d-inline-block mt-2 fw-semibold d-none"></a>
    </div>
    <hr>
    <x-button.save formId="form-import">Simpan</x-button.save>
</form>

<script>
$(function () {
    var $form = $('#form-import');
    var $box = $('#import-errors');
    var $toggle = $box.find('.import-errors-toggle');
    var previewLimit = 10;

    $form.on('submit', function () {
        $box.addClass('d-none').find('ul').empty();
        $toggle.addClass('d-none');
    });

    $toggle.on('click', function (e) {
        e.preventDefault();
        var $extra = $box.find('li.import-error-extra');
        var expand = $extra.first().hasClass('d-none');
        $extra.toggleClass('d-none', !expand);
        $toggle.text(expand ? 'Sembunyikan' : $toggle.data('label'));
    });

    $form.on('ajax-form:saved', function (e, ret) {
        $form[0].reset();
        if (!ret.import_errors || !ret.import_errors.length) {
            $('#modalku').modal('hide');
            return;
        }
        var $list = $box.find('ul');
        $.each(ret.import_errors, function (i, msg) {
            $('<li>').text(msg)
                .toggleClass('import-error-extra d-none', i >= previewLimit)
                .appendTo($list);
        });
        var rest = ret.import_errors.length - previewLimit;
        if (rest > 0) {
            var label = 'dan ' + rest + ' baris lainnya (lihat semua)';
            $toggle.data('label', label).text(label).removeClass('d-none');
        }
        $box.removeClass('d-none');
    });
});
</script>
