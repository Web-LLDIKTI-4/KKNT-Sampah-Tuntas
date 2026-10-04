/*
 * Bagian laporan yang dimuat ulang lewat AJAX tanpa reload halaman.
 * Laporan berjenjang (resources/views/laporan/_drilldown.blade.php):
 * - Host  : <div data-drilldown="url">; kosong = dimuat saat halaman siap
 * - Root  : [data-drilldown-root data-params='{"bulan","kecamatan","desa"}'] state saat ini
 * - Klik  : [data-drill='{"kecamatan": id}'] / [data-drill='{"desa": id}'] memuat ulang host
 * - Detail: [data-detail-toggle="id-baris"] buka/tutup baris detail
 * - Publik: select[name=klaster], [data-drill-reset], [data-png-download] (html-to-image lazy) — aktif hanya bila elemen ada
 * Tabel dengan filter (mis. resources/views/rekapsampah/_tabel.blade.php):
 * - Host  : <div data-filter-host="url">; select di form[data-filter] memuat ulang host
 * - Klaster: [data-klaster="hijau|kuning|merah"] memilih klaster di select name="klaster"
 */
(function ($) {
    'use strict';

    function muat($host, url, params) {
        $host.css('opacity', 0.5);
        $.get(url, params || {})
            .done(function (html) {
                $host.html(html);
            })
            .fail(function (xhr) {
                var pesan = xhr.status === 429 ? 'Terlalu banyak permintaan, coba lagi nanti.' : 'Gagal memuat laporan.';
                if (window.toastr) {
                    toastr.error(pesan);
                }
            })
            .always(function () {
                $host.css('opacity', 1);
            });
    }

    function muatDari(el, ubah) {
        var $root = $(el).closest('[data-drilldown-root]');
        var $host = $root.closest('[data-drilldown]');
        muat($host, $host.data('drilldown'), $.extend({}, $root.data('params'), ubah));
    }

    $(document).on('click', '[data-drill]', function (e) {
        e.preventDefault();
        muatDari(this, $(this).data('drill'));
    });

    $(document).on('change', '[data-drilldown-root] select[name="bulan"]', function () {
        muatDari(this, { bulan: this.value });
    });

    // Capaian publik: filter klaster & reset ke posisi default (bulan tetap)
    $(document).on('change', '[data-drilldown-root] select[name="klaster"]', function () {
        muatDari(this, { klaster: this.value });
    });

    $(document).on('click', '[data-drilldown-root] [data-drill-reset]', function () {
        muatDari(this, { kecamatan: null, desa: null, klaster: null });
    });

    var htmlToImageLoading = null;

    function loadHtmlToImage(src) {
        if (window.htmlToImage) {
            return $.Deferred().resolve().promise();
        }
        if (!htmlToImageLoading) {
            htmlToImageLoading = $.Deferred();
            var script = document.createElement('script');
            script.src = src;
            script.onload = function () { htmlToImageLoading.resolve(); };
            script.onerror = function () {
                htmlToImageLoading.reject();
                htmlToImageLoading = null;
            };
            document.head.appendChild(script);
        }
        return htmlToImageLoading.promise();
    }

    $(document).on('click', '[data-drilldown-root] [data-png-download]', function () {
        var $btn = $(this);
        var $root = $btn.closest('[data-drilldown-root]');
        var node = $root.find('[data-png-target]').get(0);
        if (!node) {
            return;
        }
        var bulan = ($root.data('params') || {}).bulan || new Date().toISOString().slice(0, 7);
        $btn.prop('disabled', true);

        loadHtmlToImage($btn.data('png-lib'))
            .then(function () {
                // Buka sementara batas scroll agar seluruh tabel ikut ter-capture
                var $boxes = $(node).find('.scroll-box').css({ maxHeight: 'none', overflow: 'visible' });
                return window.htmlToImage.toPng(node, {
                    backgroundColor: '#fff',
                    pixelRatio: 2,
                    width: node.scrollWidth,
                    height: node.scrollHeight
                }).finally(function () {
                    $boxes.css({ maxHeight: '', overflow: '' });
                });
            })
            .then(function (dataUrl) {
                var link = document.createElement('a');
                link.download = 'capaian-program-' + bulan + '.png';
                link.href = dataUrl;
                link.click();
            }, function () {
                if (window.toastr) {
                    toastr.error('Gagal membuat gambar PNG.');
                }
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });

    $(document).on('click', '[data-detail-toggle]', function () {
        $(document.getElementById($(this).data('detail-toggle'))).toggleClass('d-none');
    });

    $(document).on('change', '[data-filter-host] form[data-filter] select', function () {
        var $host = $(this).closest('[data-filter-host]');
        muat($host, $host.data('filter-host'), $(this).closest('form').serialize());
    });

    // Kartu klaster: pilih klaster (klik lagi = semua klaster)
    $(document).on('click', '[data-filter-host] [data-klaster]', function (e) {
        e.preventDefault();
        var $select = $(this).closest('[data-filter-host]').find('form[data-filter] select[name="klaster"]');
        var kode = String($(this).data('klaster'));
        $select.val($select.val() === kode ? '' : kode).trigger('change');
    });

    $(document).on('submit', '[data-filter-host] form[data-filter]', function (e) {
        e.preventDefault();
    });

    $(function () {
        $('[data-drilldown], [data-filter-host]').each(function () {
            var $host = $(this);
            if ($.trim($host.html()) === '') {
                muat($host, $host.data('drilldown') || $host.data('filter-host'));
            }
        });
    });
})(jQuery);
