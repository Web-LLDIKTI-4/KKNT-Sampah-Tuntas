<script>
$(function () {
    // Total, belum terkelola & persentase dihitung otomatis (server menghitung ulang saat simpan)
    $('body').on('input change', 'form[data-sampah-form] input', function () {
        var $form = $(this).closest('form');
        var val = function (name) { return parseFloat($form.find('[name="' + name + '"]').val()) || 0; };
        var persen = function (a, b) { return b > 0 ? (a / b * 100).toFixed(2) + '%' : '-'; };
        var timbulan = val('timbulan');
        var total = val('organik_sumber') + val('organik_dlh') + val('anorganik_sumber');

        $form.find('[name="jml_rumah_memilah"]').attr('max', val('jml_rumah'));
        $form.find('[data-hitung="persen_ketaatan"]').val(persen(val('jml_rumah_memilah'), val('jml_rumah')));
        $form.find('[data-hitung="pengurangan"]').val(total.toFixed(2));
        $form.find('[data-hitung="belum_terkelola"]').val((timbulan - total).toFixed(2));
        $form.find('[data-hitung="persen_pengurangan"]').val(persen(total, timbulan));
        $form.find('[data-hitung="peringatan"]').toggleClass('d-none', total <= timbulan);
    });
});
</script>
