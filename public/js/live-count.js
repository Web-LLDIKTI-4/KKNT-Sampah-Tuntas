/*
 * Data publik "live" (resources/views/rekapsampah/_data_sampah_publik.blade.php):
 * - Jam   : <time data-clock datetime="ISO" data-clock-tz="Asia/Jakarta" data-clock-label="WIB"> berdetak tiap detik
 *           dari waktu server (tidak bergantung jam perangkat), ditampilkan dalam zona waktu app
 * - Live  : host [data-filter-host][data-live="detik"] diperbarui diam-diam tiap N detik (hanya saat tab terlihat);
 *           angka sel tabel yang berubah dianimasikan dari nilai lama ke nilai baru
 * - Muat  : setelah 'host:loaded' (muat awal / ganti filter, dari drilldown.js) angka dihitung naik dari 0
 */
(function ($) {
    'use strict';

    var DURASI = 900;
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Format angka Indonesia dari server: "1.234,56", "-50,00%", "12"; "-" = kosong
    var POLA = /^(-?)([\d.]+)(?:,(\d+))?(%?)$/;

    function baca(teks) {
        var m = POLA.exec($.trim(teks));
        if (!m) {
            return null;
        }
        var desimal = m[3] ? m[3].length : 0;

        return {
            nilai: parseFloat(m[1] + m[2].replace(/\./g, '') + (desimal ? '.' + m[3] : '')),
            desimal: desimal,
            suffix: m[4]
        };
    }

    function tulis(nilai, desimal, suffix) {
        var bagian = Math.abs(nilai).toFixed(desimal).split('.');
        bagian[0] = bagian[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return (nilai < 0 ? '-' : '') + bagian.join(',') + suffix;
    }

    // Teks akhir selalu teks dari server (format persis sama), animasi hanya tampilan antara
    function animasi(el, dari, teksAkhir) {
        var ke = baca(teksAkhir);
        cancelAnimationFrame(el._liveRaf);
        el._liveAkhir = teksAkhir;
        if (reduce || !ke || dari === ke.nilai) {
            el.textContent = teksAkhir;
            return;
        }
        var mulai = null;
        function langkah(t) {
            if (mulai === null) {
                mulai = t;
            }
            var p = Math.min((t - mulai) / DURASI, 1);
            var e = 1 - Math.pow(1 - p, 3);
            el.textContent = p < 1 ? tulis(dari + (ke.nilai - dari) * e, ke.desimal, ke.suffix) : teksAkhir;
            if (p < 1) {
                el._liveRaf = requestAnimationFrame(langkah);
            }
        }
        el._liveRaf = requestAnimationFrame(langkah);
    }

    // Pembaca layar menunggu animasi selesai (host aria-live) agar angka antara tidak dibacakan
    function sibuk($host) {
        if (reduce) {
            return;
        }
        $host.attr('aria-busy', 'true');
        clearTimeout($host.data('liveBusy'));
        $host.data('liveBusy', setTimeout(function () { $host.removeAttr('aria-busy'); }, DURASI + 50));
    }

    function hitungNaik($host) {
        sibuk($host);
        $host.find('table td').each(function () {
            animasi(this, 0, $.trim(this.textContent));
        });
    }

    function sel($el) {
        return $el.find('table td').get();
    }

    function label($el) {
        return $el.find('table th[scope="row"]').map(function () { return $.trim(this.textContent); }).get().join('|');
    }

    function terapkan($host, html) {
        var $baru = $('<div>').append($.parseHTML(html));
        var lama = sel($host);
        var baru = sel($baru);

        // Struktur berubah (mis. lokasi baru): ganti seluruh isi
        if (lama.length !== baru.length || label($host) !== label($baru)) {
            $host.html(html).trigger('host:loaded');
            return;
        }

        var berubah = false;
        lama.forEach(function (td, i) {
            var teksBaru = $.trim(baru[i].textContent);
            if ((td._liveAkhir || $.trim(td.textContent)) === teksBaru) {
                return;
            }
            var kini = baca(td.textContent);
            animasi(td, kini ? kini.nilai : 0, teksBaru);
            td.classList.remove('ds-berubah');
            void td.offsetWidth; // restart animasi CSS
            td.classList.add('ds-berubah');
            berubah = true;
        });
        if (berubah) {
            sibuk($host);
        }

        var jamBaru = $baru.find('[data-clock]').get(0);
        $host.find('[data-clock]').each(function () {
            if (jamBaru) {
                this.setAttribute('datetime', jamBaru.getAttribute('datetime'));
                this._liveSelisih = undefined;
            }
        });
    }

    function jadwal($host) {
        clearTimeout($host.data('liveTimer'));
        var detik = parseInt($host.data('live'), 10);
        if (detik > 0) {
            $host.data('liveTimer', setTimeout(function () { segarkan($host); }, detik * 1000));
        }
    }

    function sedangMuat($host) {
        var req = $host.data('req');
        return req && req.state() === 'pending';
    }

    function segarkan($host) {
        if (document.hidden || sedangMuat($host)) {
            jadwal($host);
            return;
        }
        var params = $host.find('form[data-filter]').serialize();
        $.get($host.data('filter-host'), params)
            .done(function (html) {
                // Filter diganti selama request: hasil ini sudah usang
                if (sedangMuat($host) || $host.find('form[data-filter]').serialize() !== params) {
                    return;
                }
                terapkan($host, html);
            })
            // Gagal (mis. 429) diabaikan diam-diam; dicoba lagi di jadwal berikutnya
            .always(function () { jadwal($host); });
    }

    $(document).on('host:loaded', '[data-live]', function () {
        var $host = $(this);
        hitungNaik($host);
        jadwal($host);
    });

    // Kembali ke tab: langsung ambil data terbaru
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            $('[data-live]').each(function () { segarkan($(this)); });
        }
    });

    var formatter = {};

    function bagianWaktu(tz, waktu) {
        if (!formatter[tz]) {
            formatter[tz] = new Intl.DateTimeFormat('id-ID', {
                timeZone: tz, day: 'numeric', month: 'long', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23'
            });
        }
        var hasil = {};
        formatter[tz].formatToParts(waktu).forEach(function (p) { hasil[p.type] = p.value; });

        return hasil;
    }

    function tik() {
        var kini = Date.now();
        $('[data-clock]').each(function () {
            // Selisih jam server vs perangkat dihitung sekali per titik awal dari server
            if (this._liveSelisih === undefined) {
                this._liveSelisih = Date.parse(this.getAttribute('datetime')) - kini;
            }
            if (isNaN(this._liveSelisih)) {
                return;
            }
            try {
                var p = bagianWaktu(this.getAttribute('data-clock-tz'), new Date(kini + this._liveSelisih));
                var teks = p.day + ' ' + p.month + ' ' + p.year + ', ' + p.hour + ':' + p.minute + ':' + p.second
                    + ' ' + (this.getAttribute('data-clock-label') || '');
                if (this.textContent !== teks) {
                    this.textContent = teks;
                }
            } catch (e) {
                // Zona waktu tidak dikenal browser: biarkan teks dari server
            }
        });
    }

    setInterval(tik, 250);
})(jQuery);
