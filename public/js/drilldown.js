/*
 * Bagian laporan yang dimuat ulang lewat AJAX tanpa reload halaman.
 * Laporan berjenjang (resources/views/laporan/_drilldown.blade.php):
 * - Host  : <div data-drilldown="url">; kosong = dimuat saat halaman siap
 * - Root  : [data-drilldown-root data-params='{"bulan","kecamatan","desa"}'] state saat ini
 * - Klik  : [data-drill='{"kecamatan": id}'] / [data-drill='{"desa": id}'] memuat ulang host
 * - Detail: [data-detail-toggle="id-baris"] buka/tutup baris detail
 * - Publik: select[name=klaster], [data-drill-reset], [data-png-download] (html-to-image lazy) — aktif hanya bila elemen ada
 * - Poll  : [data-drilldown][data-poll="30"] cek ulang tiap N detik; root wajib punya data-versi (versi data server)
 * Tabel dengan filter (mis. resources/views/rekapsampah/_tabel.blade.php):
 * - Host  : <div data-filter-host="url">; select di form[data-filter] memuat ulang host
 *           isi awal kosong atau hanya [data-placeholder] (skeleton) = dimuat saat halaman siap
 * - Cari  : input[type=search] di form[data-filter] memuat ulang host otomatis (debounce), fokus tetap di input
 * - Link  : <a href> di dalam [data-filter-links] (sort, pagination) dimuat ulang di host
 * - Klaster: [data-klaster="hijau|kuning|merah"] memilih klaster di select name="klaster"
 * - Event : host memicu 'host:loaded' setelah isinya diganti (dipakai public/js/live-count.js)
 */
(function ($) {
    'use strict';

    var CARI_DELAY = 400;

    // Request sebelumnya di host yang sama dibatalkan agar respons lama tidak menimpa yang baru
    function muat($host, url, params) {
        batal($host);
        $host.css('opacity', 0.5);
        var req = $.get(url, params || {})
            .done(function (html) {
                $host.html(html).trigger('host:loaded');
            })
            .fail(function (xhr) {
                if (xhr.statusText === 'abort') {
                    return;
                }
                var pesan = xhr.status === 429 ? 'Terlalu banyak permintaan, coba lagi nanti.' : 'Gagal memuat laporan.';
                if (window.toastr) {
                    toastr.error(pesan);
                }
            })
            .always(function () {
                $host.css('opacity', 1);
            });
        $host.data('req', req);

        return req;
    }

    // Polling yang sedang jalan ikut dibatalkan agar tidak menimpa pilihan pengguna
    function batal($host) {
        $.each(['req', 'pollReq'], function (_, kunci) {
            var req = $host.data(kunci);
            if (req) {
                req.abort();
            }
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
        var bulan = ($root.data('params') || {}).bulan || 'semua';
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

    $(document).on('input', '[data-filter-host] form[data-filter] input[type="search"]', function () {
        var $host = $(this).closest('[data-filter-host]');
        var $form = $(this).closest('form');
        var nama = this.name;
        // Mengetik lagi membatalkan request yang sedang jalan agar ketikan tidak tertimpa isi lama
        clearTimeout($host.data('cariTimer'));
        batal($host);
        $host.data('cariTimer', setTimeout(function () {
            muat($host, $host.data('filter-host'), $form.serialize()).done(function () {
                var input = $host.find('form[data-filter] input[name="' + nama + '"]').get(0);
                if (input) {
                    input.focus();
                    input.setSelectionRange(input.value.length, input.value.length);
                }
            });
        }, CARI_DELAY));
    });

    $(document).on('click', '[data-filter-host] [data-filter-links] a[href]', function (e) {
        e.preventDefault();
        var $host = $(this).closest('[data-filter-host]');
        clearTimeout($host.data('cariTimer'));
        muat($host, this.href);
    });

    $(document).on('submit', '[data-filter-host] form[data-filter]', function (e) {
        e.preventDefault();
    });

    // Pengguna sedang memakai laporan: request jalan, fokus di dalam host (select terbuka), atau baris detail terbuka
    function sibuk($host) {
        var req = $host.data('req');
        if (req && req.state() === 'pending') {
            return true;
        }
        if ($.contains($host[0], document.activeElement)) {
            return true;
        }
        return $host.find('[data-detail-toggle]').toArray().some(function (el) {
            var detail = document.getElementById($(el).data('detail-toggle'));
            return detail && !detail.classList.contains('d-none');
        });
    }

    // Muat ulang senyap dengan state terakhir; isi diganti hanya bila data-versi root berubah
    function polling($host) {
        var $root = $host.find('[data-drilldown-root]');
        if (document.hidden || !$root.length || sibuk($host)) {
            return;
        }
        var lama = $host.data('pollReq');
        if (lama) {
            lama.abort();
        }
        $host.data('pollReq', $.get($host.data('drilldown'), $root.data('params')).done(function (html) {
            // DOMParser: inert, script & gambar respons tidak dijalankan/dimuat saat dicek
            var rootBaru = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-drilldown-root]');
            var versiBaru = rootBaru && rootBaru.getAttribute('data-versi');
            if (!versiBaru || versiBaru === $root.attr('data-versi') || sibuk($host)) {
                return;
            }
            $host.html(html);
        }));
    }

    // Host [data-drilldown][data-poll="detik"]; saat tab kembali aktif langsung dicek
    function mulaiPolling($host) {
        var detik = parseInt($host.data('poll'), 10);
        if (!(detik > 0)) {
            return;
        }
        setInterval(function () { polling($host); }, detik * 1000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                polling($host);
            }
        });
    }

    $(function () {
        $('[data-drilldown], [data-filter-host]').each(function () {
            var $host = $(this);
            if ($.trim($host.html()) === '' || $host.children('[data-placeholder]').length) {
                muat($host, $host.data('drilldown') || $host.data('filter-host'));
            }
        });
        $('[data-drilldown][data-poll]').each(function () {
            mulaiPolling($(this));
        });
    });
})(jQuery);
