@extends('layouts.app')
@section('title','Data Sampah Bulanan')
@section('container')
<x-crud-index
    title="Data Sampah Bulanan Kelurahan"
    :list-url="url('kpisampah/listdata')"
    :add-url="auth()->user()->akses === 'pjdesa' ? url('kpisampah/tambah') : null"
    modal-size="modal-xl" />
<script>
$(function () {
    // Pengurangan & persentase dihitung otomatis (server menghitung ulang saat simpan)
    $('body').on('input change', 'form[data-sampah-form] input', function () {
        var $form = $(this).closest('form');
        var val = function (name) { return parseFloat($form.find('[name="' + name + '"]').val()) || 0; };
        var persen = function (a, b) { return b > 0 ? (a / b * 100).toFixed(2) + '%' : '-'; };
        var pengurangan = val('pengurangan_organik') + val('pengurangan_anorganik');

        $form.find('[name="jml_rumah_memilah"]').attr('max', val('jml_rumah'));
        $form.find('[data-hitung="pengurangan"]').val(pengurangan.toFixed(2));
        $form.find('[data-hitung="persen_ketaatan"]').val(persen(val('jml_rumah_memilah'), val('jml_rumah')));
        $form.find('[data-hitung="persen_pengurangan"]').val(persen(pengurangan, val('timbulan')));
    });
});
</script>
@stop
