@extends('layouts.app')
@section('title','Capaian KPI')
@section('container')
<x-crud-index
    title="Capaian Key Performance Indicator (KPI)"
    :list-url="url('kpicapaian/listdata')"
    :add-url="auth()->user()->akses === 'pjdesa' ? url('kpicapaian/tambah') : null" />
<script>
$(function () {
    // Muat pilihan target sesuai KPI yang dipilih
    $('body').on('change', "form[data-ajax-form] select[name='id_kpi']", function () {
        $.ajax({
            url: @json(url('kpicapaian/kpitarget')),
            method: 'POST',
            data: { id_kpi: $(this).val() },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (html) {
                $('#resulttargetkpi').html(html);
                $("form[data-ajax-form] select[name='id_target']").trigger('change');
            }
        });
    });

    // Satuan realisasi mengikuti target terpilih
    $('body').on('change', "form[data-ajax-form] select[name='id_target']", function () {
        var opsi = $(this).find('option:selected');
        $(this).closest('form').find('[data-satuan-realisasi]').val(opsi.data('satuan') || '');
    });
});
</script>
@stop
