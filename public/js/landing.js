/*
 * Landing publik: count-up, navbar (scroll-spy + offcanvas), pencarian lokasi,
 * dokumen, tabel data peserta + modal detail, dan peta sebaran (lazy).
 */
(function ($) {
  "use strict";

  var scriptEl = document.currentScript || document.getElementById("landingScript");
  var leafletSrc = scriptEl ? scriptEl.getAttribute("data-leaflet-src") : "";
  var reduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  var hasIO = "IntersectionObserver" in window;

  // Count-up; angka asli tetap tampil bila JS/IO tidak tersedia
  (function () {
    var els = document.querySelectorAll(".count-up");
    if (!els.length || reduceMotion || !hasIO) return;
    var fmt = new Intl.NumberFormat("id-ID");
    var run = function (el) {
      var target = parseInt(el.dataset.target, 10) || 0, start = null, dur = 1600;
      var step = function (ts) {
        if (start === null) start = ts;
        var p = Math.min((ts - start) / dur, 1);
        el.textContent = fmt.format(Math.round(target * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { io.unobserve(e.target); run(e.target); }
      });
    }, { threshold: 0.5 });
    els.forEach(function (el) { el.textContent = "0"; io.observe(el); });
  })();

  // Reveal on scroll; content is only hidden after html.js-reveal is set here
  (function () {
    if (reduceMotion || !hasIO) return;
    document.querySelectorAll("[data-reveal-children]").forEach(function (group) {
      Array.prototype.forEach.call(group.children, function (child) {
        if (!child.hasAttribute("data-reveal")) child.setAttribute("data-reveal", "");
      });
    });
    var items = document.querySelectorAll("[data-reveal]");
    if (!items.length) return;
    var DURATION = 600, STEP = 70, MAX_DELAY = 280;
    var io = new IntersectionObserver(function (entries) {
      var batch = 0;
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var el = e.target, delay = Math.min(batch++ * STEP, MAX_DELAY);
        io.unobserve(el);
        el.style.setProperty("--reveal-delay", delay + "ms");
        el.classList.add("is-visible");
        // Drop reveal styles afterwards so hover transforms and fixed children behave normally
        setTimeout(function () {
          el.removeAttribute("data-reveal");
          el.classList.remove("is-visible");
          el.style.removeProperty("--reveal-delay");
        }, DURATION + delay + 80);
      });
    }, { threshold: 0.1 });
    document.documentElement.classList.add("js-reveal");
    items.forEach(function (el) { io.observe(el); });
  })();

  $(function () {
    var nav = document.getElementById("lpNav");
    var offcanvasEl = document.getElementById("lpOffcanvas");

    // Navbar: bayangan setelah scroll
    var onScroll = function () {
      nav.classList.toggle("is-scrolled", window.scrollY > 8);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();

    // Scroll-spy: tandai link section yang sedang terlihat
    var spyLinks = document.querySelectorAll("[data-spy]");
    if (hasIO && spyLinks.length) {
      var setActive = function (id) {
        spyLinks.forEach(function (a) {
          var on = a.getAttribute("data-spy") === id;
          a.classList.toggle("active", on);
          if (on) a.setAttribute("aria-current", "true"); else a.removeAttribute("aria-current");
        });
      };
      var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) setActive(e.target.id);
        });
      }, { rootMargin: "-45% 0px -50% 0px" });
      document.querySelectorAll("main > section[id]").forEach(function (s) { spy.observe(s); });
    }

    function goTo(id) {
      var target = document.getElementById(id);
      if (!target) return;
      target.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });
      target.focus({ preventScroll: true });
      if (history.replaceState) history.replaceState(null, "", "#" + id);
    }

    // Offcanvas: toggled manually so Bootstrap's focus-restore (which scrolls the sticky burger into view) never runs
    if (offcanvasEl) {
      var offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
      var burger = document.querySelector(".lp-burger");
      var pendingId = null;
      if (burger) burger.addEventListener("click", function () { offcanvas.toggle(burger); });
      $(offcanvasEl).on("click", "a[href^='#']", function (e) {
        var id = this.getAttribute("href").slice(1);
        if (!document.getElementById(id)) return;
        e.preventDefault();
        pendingId = id;
        offcanvas.hide();
      });
      // Scroll only after the close animation, once the body scroll lock is released
      offcanvasEl.addEventListener("hidden.bs.offcanvas", function () {
        var id = pendingId;
        pendingId = null;
        if (id) goTo(id);
        else if (burger) burger.focus({ preventScroll: true });
      });
    }

    // Link #panduan lama diarahkan ke section Dokumen
    if (window.location.hash === "#panduan") {
      goTo("dokumen");
    }

    // Pencarian kartu lokasi
    $("#lokasiSearch").on("input", function () {
      var keyword = $(this).val().toLowerCase().trim();
      var visible = 0;
      $("#lokasiGrid .lokasi-item").each(function () {
        var match = String($(this).data("lokasi")).indexOf(keyword) !== -1;
        this.hidden = !match;
        if (match) visible++;
      });
      $("#lokasiNoResult").toggleClass("d-none", visible > 0);
    });

    $("#panduanSearch").on("input", function () {
      var keyword = $(this).val().toLowerCase().trim();
      var $items = $("#panduanList .panduan-item");
      var visibleCount = 0;

      $items.each(function () {
        var isMatch = String($(this).data("search")).indexOf(keyword) !== -1;
        $(this).toggleClass("d-none", !isMatch);
        if (isMatch) {
          visibleCount++;
        }
      });

      var total = $("#panduanCount").data("total");
      $("#panduanCount").text(keyword === "" ? total + " dokumen" : visibleCount + " dari " + total + " dokumen");
      $("#panduanList").toggleClass("d-none", visibleCount === 0);
      $("#panduanNoResult").toggleClass("d-none", visibleCount > 0);
    });

    // Pagination tabel sisi klien (peserta & sebaran PT)
    var pageItem = function (label, page, opts) {
      opts = opts || {};
      var cls = "page-item" + (opts.disabled ? " disabled" : "") + (opts.active ? " active" : "");
      var attrs = opts.active ? ' aria-current="page"' : "";
      if (opts.aria) {
        attrs += ' aria-label="' + opts.aria + '"';
      }
      if (opts.disabled) {
        attrs += " disabled";
      }
      return '<li class="' + cls + '"><button type="button" class="page-link" data-page="' + page + '"' + attrs + ">" + label + "</button></li>";
    };

    // Jendela halaman: pertama, terakhir, aktif ±1; celah 1 halaman diisi angka
    var paginationHtml = function (current, pages) {
      var nums = [];
      for (var p = 1; p <= pages; p++) {
        if (p === 1 || p === pages || Math.abs(p - current) <= 1 ||
            (p === 2 && current === 4) || (p === pages - 1 && current === pages - 3)) {
          nums.push(p);
        }
      }

      var html = pageItem('<i class="ri-arrow-left-s-line" aria-hidden="true"></i>', current - 1, { disabled: current === 1, aria: "Sebelumnya" });
      nums.forEach(function (n, i) {
        if (i > 0 && n - nums[i - 1] > 1) {
          html += '<li class="page-item disabled"><span class="page-link" aria-hidden="true">…</span></li>';
        }
        html += pageItem(n, n, { active: n === current, aria: "Halaman " + n });
      });
      html += pageItem('<i class="ri-arrow-right-s-line" aria-hidden="true"></i>', current + 1, { disabled: current === pages, aria: "Berikutnya" });
      return html;
    };

    // Sebaran PT: filter, limit & pagination di sisi klien
    var $sebaranTbody = $("#sebaranTbody");
    if ($sebaranTbody.length) {
      var $sebaranRows = $sebaranTbody.children("tr");
      var $sebaranSearch = $("#sebaranSearch");
      var $sebaranLimit = $("#sebaranLimit");
      var $sebaranPagination = $("#sebaranPagination");
      var $sebaranWrap = $sebaranTbody.closest(".peserta-table-wrap");
      var sebaranPage = 1;
      var sebaranTimer = null;

      var sebaranToTop = function () {
        $sebaranWrap.scrollTop(0);
        if ($sebaranWrap[0].getBoundingClientRect().top < 0) {
          $sebaranWrap[0].scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });
        }
      };

      var renderSebaran = function () {
        var keyword = String($sebaranSearch.val() || "").toLowerCase().trim();
        var limit = parseInt($sebaranLimit.val(), 10) || 0;
        var matched = keyword === "" ? $sebaranRows : $sebaranRows.filter(function () {
          return String($(this).attr("data-search")).indexOf(keyword) !== -1;
        });
        var count = matched.length;
        var perPage = limit > 0 ? limit : Math.max(count, 1);
        var pages = Math.max(1, Math.ceil(count / perPage));
        sebaranPage = Math.min(Math.max(1, sebaranPage), pages);
        var start = (sebaranPage - 1) * perPage;
        var end = Math.min(start + perPage, count);
        var fmt = function (n) { return n.toLocaleString("id-ID"); };

        $sebaranRows.addClass("d-none");
        matched.slice(start, end).each(function (i) {
          $(this).removeClass("d-none").children(".peserta-no").text(start + i + 1);
        });
        $sebaranTbody.toggleClass("d-none", count === 0);
        $("#sebaranNoResult").toggleClass("d-none", count > 0);
        $("#sebaranInfo").text(count === 0
          ? "Tidak ada hasil"
          : "Menampilkan " + fmt(start + 1) + "–" + fmt(end) + " dari " + fmt(count) + (keyword === "" ? " baris" : " hasil"));

        if (pages <= 1) {
          $sebaranPagination.empty().closest("nav").addClass("d-none");
          return;
        }
        $sebaranPagination.html(paginationHtml(sebaranPage, pages)).closest("nav").removeClass("d-none");
      };

      $sebaranSearch.on("input", function () {
        clearTimeout(sebaranTimer);
        sebaranTimer = setTimeout(function () {
          sebaranPage = 1;
          renderSebaran();
        }, 200);
      });

      $sebaranLimit.on("change", function () {
        sebaranPage = 1;
        renderSebaran();
      });

      $sebaranTbody.closest("table").on("table:sorted", function () {
        $sebaranRows = $sebaranTbody.children("tr");
        sebaranPage = 1;
        renderSebaran();
        sebaranToTop();
      });

      $sebaranPagination.on("click", "button.page-link", function () {
        var target = parseInt($(this).data("page"), 10);
        if ($(this).prop("disabled") || !target || target === sebaranPage) {
          return;
        }
        sebaranPage = target;
        renderSebaran();
        sebaranToTop();
      });

      renderSebaran();
    }

    // Data peserta: filter, limit & pagination di sisi klien
    var $pesertaTbody = $("#pesertaTbody");
    if ($pesertaTbody.length) {
      var $pesertaRows = $pesertaTbody.children("tr");
      var $pesertaSearch = $("#pesertaSearch");
      var $pesertaLimit = $("#pesertaLimit");
      var $pesertaInfo = $("#pesertaInfo");
      var $pesertaPagination = $("#pesertaPagination");
      var $pesertaNoResult = $("#pesertaNoResult");
      var $pesertaWrap = $pesertaTbody.closest(".peserta-table-wrap");
      var pesertaPage = 1;
      var pesertaTimer = null;

      // Desktop: wrap scrolls internally; mobile: bring table top back into view
      var pesertaToTop = function () {
        $pesertaWrap.scrollTop(0);
        var top = $pesertaWrap[0].getBoundingClientRect().top;
        if (top < 0) {
          $pesertaWrap[0].scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });
        }
      };

      var formatNum = function (n) {
        return n.toLocaleString("id-ID");
      };

      var renderPeserta = function () {
        var keyword = String($pesertaSearch.val() || "").toLowerCase().trim();
        var limit = parseInt($pesertaLimit.val(), 10) || 0;
        var total = $pesertaRows.length;
        var matched = keyword === "" ? $pesertaRows : $pesertaRows.filter(function () {
          return String($(this).attr("data-search")).indexOf(keyword) !== -1;
        });
        var count = matched.length;
        var perPage = limit > 0 ? limit : Math.max(count, 1);
        var pages = Math.max(1, Math.ceil(count / perPage));
        pesertaPage = Math.min(Math.max(1, pesertaPage), pages);
        var start = (pesertaPage - 1) * perPage;
        var end = Math.min(start + perPage, count);

        $pesertaRows.addClass("d-none");
        matched.slice(start, end).each(function (i) {
          $(this).removeClass("d-none").children(".peserta-no").text(start + i + 1);
        });

        $pesertaTbody.toggleClass("d-none", count === 0);
        $pesertaNoResult.toggleClass("d-none", count > 0);

        var suffix = keyword === "" ? "" : " (total " + formatNum(total) + ")";
        var info = count === 0
          ? (keyword === "" ? "Tidak ada data perguruan tinggi" : "Tidak ada hasil" + suffix)
          : "Menampilkan " + formatNum(start + 1) + "–" + formatNum(end) + " dari " + formatNum(count) + (keyword === "" ? " perguruan tinggi" : " hasil" + suffix);
        $pesertaInfo.text(info);

        if (pages <= 1) {
          $pesertaPagination.empty().closest("nav").addClass("d-none");
          return;
        }

        $pesertaPagination.html(paginationHtml(pesertaPage, pages)).closest("nav").removeClass("d-none");
      };

      $pesertaSearch.on("input", function () {
        clearTimeout(pesertaTimer);
        pesertaTimer = setTimeout(function () {
          pesertaPage = 1;
          renderPeserta();
        }, 200);
      });

      $pesertaLimit.on("change", function () {
        pesertaPage = 1;
        renderPeserta();
      });

      // Sort header (table-sort.js): ambil urutan baris baru lalu render ulang dari halaman 1
      $pesertaTbody.closest("table").on("table:sorted", function () {
        $pesertaRows = $pesertaTbody.children("tr");
        pesertaPage = 1;
        renderPeserta();
        pesertaToTop();
      });

      $pesertaPagination.on("click", "button.page-link", function () {
        var target = parseInt($(this).data("page"), 10);
        if ($(this).prop("disabled") || !target || target === pesertaPage) {
          return;
        }
        pesertaPage = target;
        renderPeserta();
        pesertaToTop();
      });

      // Detail peserta per PT: list dimuat sekali, search/limit/pagination di sisi klien
      var $pesertaModal = $("#modalPesertaDetail");
      var $detailWrap = $pesertaModal.find(".peserta-detail-wrap");
      var $detailStatus = $pesertaModal.find(".peserta-detail-status");
      var $detailHead = $pesertaModal.find("thead tr");
      var $detailTbody = $("#pesertaDetailTbody");
      var $detailTable = $detailTbody.closest("table");
      var $detailNoResult = $pesertaModal.find(".peserta-detail-noresult");
      var $detailSearch = $("#pesertaDetailSearch");
      var $detailLimit = $("#pesertaDetailLimit");
      var $detailInfo = $("#pesertaDetailInfo");
      var $detailPagination = $("#pesertaDetailPagination");
      var pesertaLabels = { mahasiswa: "Mahasiswa", dpl: "DPL", pic: "PIC", ketua: "Ketua Kelompok" };
      var detailCollator = new Intl.Collator("id");
      var pesertaXhr = null;
      var detailTimer = null;
      var detailRows = [];
      var detailPage = 1;

      var pesertaDetailStatus = function (text) {
        $detailWrap.addClass("d-none");
        $detailStatus.text(text).removeClass("d-none");
      };

      var renderPesertaDetail = function () {
        var keyword = String($detailSearch.val() || "").toLowerCase().trim();
        var limit = parseInt($detailLimit.val(), 10) || 10;
        var total = detailRows.length;
        var matched = keyword === "" ? detailRows : detailRows.filter(function (row) {
          return row.search.indexOf(keyword) !== -1;
        });
        var count = matched.length;
        var pages = Math.max(1, Math.ceil(count / limit));
        detailPage = Math.min(Math.max(1, detailPage), pages);
        var start = (detailPage - 1) * limit;
        var end = Math.min(start + limit, count);

        $detailTbody.empty().append(matched.slice(start, end).map(function (row, i) {
          var $tr = $("<tr>").append($('<td class="text-center">').text(start + i + 1));
          // cells[0] = NIM/NIDN/Kode PT (numeric column)
          row.cells.forEach(function (val, j) {
            $tr.append($("<td>").toggleClass("text-center", j === 0).text(val === "" ? "–" : val));
          });
          return $tr[0];
        }));

        $detailTable.toggleClass("d-none", count === 0);
        $detailNoResult.toggleClass("d-none", count > 0);

        var suffix = keyword === "" ? "" : " (difilter dari " + formatNum(total) + ")";
        $detailInfo.text(count === 0
          ? "Tidak ada data yang cocok" + suffix
          : "Menampilkan " + formatNum(start + 1) + "–" + formatNum(end) + " dari " + formatNum(count) + " data" + suffix);

        if (pages <= 1) {
          $detailPagination.empty().closest("nav").addClass("d-none");
          return;
        }
        $detailPagination.html(paginationHtml(detailPage, pages)).closest("nav").removeClass("d-none");
      };

      var stopPesertaDetail = function () {
        clearTimeout(detailTimer);
        if (pesertaXhr) {
          pesertaXhr.abort();
        }
      };

      $pesertaTbody.on("click", ".peserta-detail", function () {
        var kodept = String($(this).data("kodept"));
        var jenis = String($(this).data("jenis"));
        var label = pesertaLabels[jenis] || "Peserta";
        var namaPt = $(this).closest("tr").children("td").eq(1).text().trim();
        var isPic = jenis === "pic";
        var headers = isPic ? ["No", "Kode PT", "Nama PT", "Nama User"] : ["No", jenis === "dpl" ? "NIDN" : "NIM", "Nama"];
        var sortIdx = isPic ? 2 : 1;

        // Reset state tiap modal dibuka
        stopPesertaDetail();
        detailRows = [];
        detailPage = 1;
        $detailSearch.val("");
        $detailLimit.val("10");
        $detailTbody.empty();
        $detailHead.empty().append(headers.map(function (text, i) {
          return $('<th scope="col" class="text-center">').text(text)[0];
        }));

        $("#modalPesertaDetailTitle").text(label + " - " + namaPt);
        pesertaDetailStatus("Memuat data…");
        bootstrap.Modal.getOrCreateInstance($pesertaModal[0]).show();

        pesertaXhr = $.getJSON($pesertaTbody.data("detail-url"), { kodept: kodept, jenis: jenis })
          .done(function (res) {
            var list = (res && Array.isArray(res.data)) ? res.data : [];
            if (res && res.nama_pt) {
              $("#modalPesertaDetailTitle").text(label + " - " + res.nama_pt);
            }
            if (!list.length) {
              pesertaDetailStatus("Belum ada data " + label.toLowerCase() + ".");
              return;
            }
            detailRows = list.map(function (row) {
              row = row || {};
              var cells = (isPic ? [row.kode_pt, row.nama_pt, row.nama_user] : [row.nim, row.nama]).map(function (val) {
                return val === null || val === undefined ? "" : String(val).trim();
              });
              return { cells: cells, search: cells.join("\n").toLowerCase() };
            });
            detailRows.sort(function (a, b) {
              return detailCollator.compare(a.cells[sortIdx], b.cells[sortIdx]);
            });
            renderPesertaDetail();
            $detailStatus.addClass("d-none");
            $detailWrap.removeClass("d-none");
          })
          .fail(function (xhr, status) {
            if (status === "abort") {
              return;
            }
            pesertaDetailStatus(xhr.status === 429
              ? "Terlalu banyak permintaan, coba lagi sebentar."
              : "Gagal memuat data. Silakan coba lagi.");
          })
          .always(function () {
            pesertaXhr = null;
          });
      });

      // Handler kontrol modal di-bind sekali
      $detailSearch.on("input", function () {
        clearTimeout(detailTimer);
        detailTimer = setTimeout(function () {
          detailPage = 1;
          renderPesertaDetail();
        }, 200);
      });

      $detailLimit.on("change", function () {
        detailPage = 1;
        renderPesertaDetail();
      });

      $detailPagination.on("click", "button.page-link", function () {
        var target = parseInt($(this).data("page"), 10);
        if ($(this).prop("disabled") || !target || target === detailPage) {
          return;
        }
        detailPage = target;
        renderPesertaDetail();
        $pesertaModal.find(".modal-body").scrollTop(0);
      });

      $pesertaModal.on("hide.bs.modal", stopPesertaDetail);

      renderPeserta();
    }

    // Daftar status (badge sudah/belum): paginasi & pencarian di server
    var $statusModal = $("#modalStatusList");
    if ($statusModal.length) {
      var statusColumns = {
        mobilisasi: [["NIM", "nim"], ["Nama Ketua", "nama"], ["PTS", "nama_pt"], ["Kecamatan", "kecamatan"], ["Desa", "desa"]],
        pelaksanaan: [["Kode PT", "kode_pt"], ["Nama PTS", "nama_pt"]],
        lokasi: [["NIM", "nim"], ["Nama", "nama"], ["PTS", "nama_pt"], ["Lokasi Program", "lokasi"], ["Kecamatan", "kecamatan"], ["Desa", "desa"]]
      };
      // Kolom angka: rata tengah (header & sel)
      var statusCentered = { nim: true, kode_pt: true };
      var $statusMsg = $statusModal.find(".status-list-status");
      var $statusWrap = $statusModal.find(".status-list-wrap");
      var $statusTableWrap = $statusModal.find(".status-list-table");
      var $statusHead = $statusModal.find("thead tr");
      var $statusTbody = $("#statusListTbody");
      var $statusNoResult = $statusModal.find(".status-list-noresult");
      var $statusSearch = $("#statusListSearch");
      var $statusLimit = $("#statusListLimit");
      var $statusInfo = $("#statusListInfo");
      var $statusPagination = $("#statusListPagination");
      var statusState = { jenis: "", status: "", page: 1, q: "", sort: "", dir: "" };
      var statusXhr = null;
      var statusTimer = null;
      var statusLoaded = false;

      var statusNum = function (n) {
        return (Number(n) || 0).toLocaleString("id-ID");
      };

      // Pesan + tombol "Coba lagi" (opsional) di elemen target
      var statusRetryTimer = null;
      var statusRetryUntil = 0;

      var statusMessage = function ($el, text, retry) {
        $el.empty().append(document.createTextNode(text));
        if (retry) {
          // aria-live off: hitung mundur per detik tidak dibacakan berulang
          return $('<button type="button" class="btn btn-sm btn-outline-primary" aria-live="off">Coba lagi</button>')
            .on("click", function () { statusFetch(); })
            .appendTo($el);
        }
      };

      var statusStopCountdown = function () {
        clearInterval(statusRetryTimer);
        statusRetryTimer = null;
      };

      // Tombol retry disabled sampai Retry-After habis
      var statusCountdown = function ($btn) {
        statusStopCountdown();
        var tick = function () {
          var left = Math.ceil((statusRetryUntil - Date.now()) / 1000);
          if (left <= 0) {
            statusStopCountdown();
            $btn.prop("disabled", false).text("Coba lagi");
            return;
          }
          $btn.prop("disabled", true).text("Coba lagi dalam " + left + " detik");
        };
        tick();
        if (Date.now() < statusRetryUntil) {
          statusRetryTimer = setInterval(tick, 1000);
        }
      };

      var statusAbort = function () {
        statusStopCountdown();
        clearTimeout(statusTimer);
        if (statusXhr) {
          statusXhr.abort();
          statusXhr = null;
        }
      };

      // Sinkronkan aria-sort & indikator panah dengan statusState
      var statusSortUi = function () {
        $statusHead.find(".status-sort").each(function () {
          var active = $(this).data("sort") === statusState.sort;
          var $th = $(this).closest("th");
          if (active) {
            $th.attr("aria-sort", statusState.dir === "desc" ? "descending" : "ascending");
          } else {
            $th.removeAttr("aria-sort");
          }
          $(this).toggleClass("is-active", active)
            .find(".status-sort-ind").text(active ? (statusState.dir === "desc" ? "▼" : "▲") : "↕");
        });
      };

      var statusRender = function (res) {
        var cols = statusColumns[statusState.jenis];
        var rows = Array.isArray(res.data) ? res.data : [];
        var meta = res.meta || {};
        var total = Number(meta.total) || 0;
        var from = Number(meta.from) || 0;
        var lastPage = Math.max(1, Number(meta.last_page) || 1);
        statusState.page = Number(meta.page) || statusState.page;
        if (meta.sort && cols.some(function (col) { return col[1] === meta.sort; })) {
          statusState.sort = meta.sort;
          statusState.dir = meta.dir === "desc" ? "desc" : "asc";
          statusSortUi();
        }

        // Kosong tanpa pencarian: tidak ada data sama sekali
        if (total === 0 && statusState.q === "" && !statusLoaded) {
          $statusWrap.addClass("d-none");
          statusMessage($statusMsg.removeClass("d-none"), "Belum ada data.");
          return;
        }
        statusLoaded = true;
        $statusMsg.addClass("d-none").empty();
        $statusWrap.removeClass("d-none is-loading").removeAttr("aria-busy");

        $statusTbody.empty().append(rows.map(function (row, i) {
          var $tr = $("<tr>").append($('<td class="text-center">').text(statusNum(from + i)));
          cols.forEach(function (col) {
            var val = row ? row[col[1]] : null;
            val = val === null || val === undefined ? "" : String(val).trim();
            $tr.append($("<td>").toggleClass("text-center", statusCentered[col[1]] === true).text(val === "" ? "-" : val));
          });
          return $tr[0];
        }));

        $statusTableWrap.toggleClass("d-none", rows.length === 0);
        $statusNoResult.toggleClass("d-none", rows.length > 0);
        if (!rows.length) {
          statusMessage($statusNoResult, "Data tidak ditemukan.");
        }

        $statusInfo.text(total === 0
          ? "Tidak ada data yang cocok"
          : "Menampilkan " + statusNum(from) + "–" + statusNum(meta.to) + " dari " + statusNum(total) + " data");

        if (lastPage <= 1) {
          $statusPagination.empty().closest("nav").addClass("d-none");
          return;
        }
        $statusPagination.html(paginationHtml(statusState.page, lastPage)).closest("nav").removeClass("d-none");
      };

      var statusShowError = function (throttled) {
        var text = throttled ? "Terlalu banyak permintaan." : "Gagal memuat data. Silakan coba lagi.";
        var $btn;
        if (!statusLoaded) {
          $statusWrap.addClass("d-none");
          $btn = statusMessage($statusMsg.removeClass("d-none"), text, true);
        } else {
          $statusWrap.removeClass("is-loading").removeAttr("aria-busy");
          $statusTableWrap.addClass("d-none");
          $statusPagination.empty().closest("nav").addClass("d-none");
          $statusInfo.text("");
          $btn = statusMessage($statusNoResult.removeClass("d-none"), text, true);
        }
        if (throttled) {
          statusCountdown($btn);
        }
      };

      var statusFail = function (xhr) {
        var throttled = xhr.status === 429;
        if (throttled) {
          var wait = parseInt(xhr.getResponseHeader("Retry-After"), 10);
          statusRetryUntil = Date.now() + (wait > 0 ? wait : 60) * 1000;
        }
        statusShowError(throttled);
      };

      var statusFetch = function () {
        statusAbort();
        // Masih dalam jeda throttle: tampilkan ulang state 429 tanpa request
        if (Date.now() < statusRetryUntil) {
          statusShowError(true);
          return;
        }
        if (statusLoaded) {
          // Kontrol tetap tampil agar fokus input pencarian tidak hilang
          $statusWrap.addClass("is-loading").attr("aria-busy", "true");
        } else {
          $statusWrap.addClass("d-none");
          statusMessage($statusMsg.removeClass("d-none"), "Memuat data…");
        }

        var params = {
          jenis: statusState.jenis,
          status: statusState.status,
          page: statusState.page,
          per_page: $statusLimit.val() === "25" ? 25 : 10
        };
        if (statusState.q !== "") {
          params.q = statusState.q;
        }
        if (statusState.sort !== "") {
          params.sort = statusState.sort;
          params.dir = statusState.dir;
        }

        var xhr = $.ajax({ url: $statusModal.data("url"), data: params, dataType: "json" });
        statusXhr = xhr;
        xhr.done(function (res) {
          statusRender(res || {});
        }).fail(function (jq, status) {
          if (status !== "abort") {
            statusFail(jq);
          }
        }).always(function () {
          if (statusXhr === xhr) {
            statusXhr = null;
          }
        });
      };

      $(document).on("click", ".status-badge-btn", function () {
        var jenis = String($(this).data("jenis"));
        var status = String($(this).data("status"));
        if (!statusColumns[jenis] || (status !== "sudah" && status !== "belum")) {
          return;
        }

        // Reset state tiap modal dibuka
        statusAbort();
        statusState = { jenis: jenis, status: status, page: 1, q: "", sort: "", dir: "" };
        statusLoaded = false;
        $statusSearch.val("");
        $statusLimit.val("10");
        $statusTbody.empty();
        $statusNoResult.addClass("d-none").empty();
        $statusTableWrap.removeClass("d-none");
        $statusHead.empty().append([["No"]].concat(statusColumns[jenis]).map(function (col) {
          var $th = $('<th scope="col">').toggleClass("text-center", col[0] === "No" || statusCentered[col[1]] === true);
          if (!col[1]) {
            return $th.text(col[0])[0];
          }
          return $th.append($('<button type="button" class="table-sort status-sort">').attr("data-sort", col[1])
            .text(col[0]).append('<span class="status-sort-ind" aria-hidden="true"></span>'))[0];
        }));
        statusSortUi();
        $("#modalStatusListTitle").text(String($(this).data("title")));

        bootstrap.Modal.getOrCreateInstance($statusModal[0]).show();
        statusFetch();
      });

      // Debounce 400ms; minimal 2 karakter (atau kosong) agar hemat kuota throttle
      $statusSearch.on("input", function () {
        clearTimeout(statusTimer);
        var q = String($statusSearch.val() || "").trim().slice(0, 50);
        if (q === statusState.q || (q !== "" && q.length < 2)) {
          return;
        }
        statusTimer = setTimeout(function () {
          statusState.q = q;
          statusState.page = 1;
          statusFetch();
        }, 400);
      });

      $statusLimit.on("change", function () {
        statusState.page = 1;
        statusFetch();
      });

      // Sort di server: klik pertama asc, berikutnya bergantian desc/asc
      $statusHead.on("click", ".status-sort", function () {
        var key = String($(this).data("sort"));
        statusState.dir = key === statusState.sort && statusState.dir === "asc" ? "desc" : "asc";
        statusState.sort = key;
        statusState.page = 1;
        statusSortUi();
        statusFetch();
      });

      $statusPagination.on("click", "button.page-link", function () {
        var target = parseInt($(this).data("page"), 10);
        if ($(this).prop("disabled") || !target || target === statusState.page) {
          return;
        }
        statusState.page = target;
        statusFetch();
        $statusModal.find(".modal-body").scrollTop(0);
      });

      $statusModal.on("hide.bs.modal", statusAbort);
    }

    // Peta sebaran: Leaflet dimuat saat section terlihat; respons di-cache per kombinasi filter
    var petaMap = null;
    var petaLayer = null;
    var petaMarkers = [];
    var petaOptions = null;
    var petaFilter = { tahun: null, kodept: null };
    var petaCache = {};
    var petaSeq = 0;
    var petaTimer = null;
    var petaBooting = false;
    var petaFitted = false;
    var petaBounds = null;
    // Listener moveend milik petaFocus yang belum jalan
    var petaFocusPending = null;
    var petaReduceMotion = reduceMotion;
    // Index = tier dari server (0 = tanpa mahasiswa)
    // color = tepi gradien, light = pusat, stroke = border (versi gelap)
    var petaTiers = [
      { color: "#b8bfc8", light: "#d9dee3", stroke: "#8a939d", radius: 5 },
      { color: "#4fb286", light: "#93d6b6", stroke: "#2f8a61", radius: 7 },
      { color: "#4aaed6", light: "#90d0ec", stroke: "#2a86ad", radius: 10 },
      { color: "#5b7fe0", light: "#9ab3f2", stroke: "#3a5cc4", radius: 13 },
      { color: "#9b74d4", light: "#c6a9ec", stroke: "#7650b5", radius: 16 },
      { color: "#e8706b", light: "#f4a6a1", stroke: "#c94a45", radius: 20 }
    ];
    var petaLegendItems = [[1, "< 6"], [2, "6–10"], [3, "11–20"], [4, "21–30"], [5, "> 30"]];

    function petaEl(tag, className, text) {
      var el = document.createElement(tag);
      if (className) el.className = className;
      if (text !== undefined && text !== null) el.textContent = text;
      return el;
    }

    function petaStatus(text, retry) {
      $("#petaStatusText").text(text || "");
      $("#petaRetry").toggleClass("d-none", !retry);
      $("#petaStatus").toggleClass("d-none", !text);
    }

    function petaList(value) {
      return Array.isArray(value) ? value : [];
    }

    // null/non-numerik -> null; selain itu angka berhingga
    function petaPersen(value) {
      if (value === null || value === undefined || value === "") return null;
      var num = Number(value);
      return Number.isFinite(num) ? num : null;
    }

    function petaPersenText(value) {
      return value.toLocaleString("id-ID", { maximumFractionDigits: 2 }) + "%";
    }

    function petaReduksiText(row, periode) {
      var persen = petaPersen(row.persen_pengurangan);
      if (persen === null) return "Pengurangan sampah: Belum ada data";
      var label = periode && periode.label ? " (" + periode.label + ")" : "";
      return "Pengurangan sampah: " + petaPersenText(persen) + label;
    }

    function petaPopup(row, periode) {
      var wrap = petaEl("div");
      wrap.appendChild(petaEl("strong", null, row.desa));
      if (row.kecamatan) wrap.appendChild(petaEl("div", "peta-muted", "Kec. " + row.kecamatan));
      wrap.appendChild(petaEl("div", null, (row.jumlah_label || "0") + " mahasiswa"));
      wrap.appendChild(petaEl("div", "peta-muted", petaReduksiText(row, periode)));
      return wrap;
    }

    function petaLoadLeaflet(done, fail) {
      if (window.L) {
        done();
        return;
      }
      var script = document.createElement("script");
      script.src = leafletSrc;
      script.onload = done;
      script.onerror = function () {
        script.remove();
        fail();
      };
      document.body.appendChild(script);
    }

    function petaInitMap() {
      // Pusat awal Jawa Barat; segera diganti fitBounds bila ada data
      petaMap = L.map("petaSebaran", { scrollWheelZoom: false }).setView([-6.92, 107.6], 9);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
      }).addTo(petaMap);
      petaLayer = L.featureGroup().addTo(petaMap);

      var legend = L.control({ position: "bottomleft" });
      legend.onAdd = function () {
        var box = petaEl("div", "peta-legend");
        box.appendChild(petaEl("div", "fw-bold mb-1", "Densitas Mahasiswa"));
        petaLegendItems.forEach(function (item) {
          var tier = petaTiers[item[0]];
          var row = petaEl("div", "peta-legend-row");
          var swatch = petaEl("span", "peta-legend-swatch");
          var dot = petaEl("span", "peta-legend-dot");
          dot.style.width = dot.style.height = tier.radius + "px";
          dot.style.background = "radial-gradient(circle at 35% 30%, " + tier.light + ", " + tier.color + " 70%)";
          dot.style.border = "1.5px " + (item[0] > 0 ? "solid " : "dashed ") + tier.stroke;
          swatch.appendChild(dot);
          row.appendChild(swatch);
          row.appendChild(petaEl("span", null, item[1]));
          box.appendChild(row);
        });
        L.DomEvent.disableClickPropagation(box);
        return box;
      };
      legend.addTo(petaMap);
      petaGradients();
    }

    // Satu set radialGradient dipakai semua bubble (tanpa filter per path)
    function petaGradients() {
      if (document.getElementById("petaGrad0")) return;
      var ns = "http://www.w3.org/2000/svg";
      var svg = document.createElementNS(ns, "svg");
      var defs = document.createElementNS(ns, "defs");
      svg.setAttribute("width", "0");
      svg.setAttribute("height", "0");
      svg.setAttribute("aria-hidden", "true");
      svg.style.position = "absolute";
      petaTiers.forEach(function (tier, i) {
        var grad = document.createElementNS(ns, "radialGradient");
        grad.setAttribute("id", "petaGrad" + i);
        grad.setAttribute("cx", "35%");
        grad.setAttribute("cy", "30%");
        grad.setAttribute("r", "75%");
        // Stop tengah 70%: area pucat dipersempit agar isi lebih pekat
        [["0%", tier.light], ["70%", tier.color], ["100%", tier.color]].forEach(function (s) {
          var stop = document.createElementNS(ns, "stop");
          stop.setAttribute("offset", s[0]);
          stop.setAttribute("stop-color", s[1]);
          grad.appendChild(stop);
        });
        defs.appendChild(grad);
      });
      svg.appendChild(defs);
      document.getElementById("petaSebaran").appendChild(svg);
    }

    function petaBuildFilters() {
      var tahun = document.getElementById("petaTahun");
      var pt = document.getElementById("petaPt");

      tahun.length = 0;
      petaList(petaOptions.tahun).forEach(function (year) {
        tahun.add(new Option(String(year), String(year)));
      });

      pt.length = 0;
      pt.add(new Option("Semua PT", ""));
      petaList(petaOptions.pt).forEach(function (item) {
        pt.add(new Option(item.nama, String(item.kodept)));
      });
    }

    function petaSyncControls() {
      $("#petaTahun").val(petaFilter.tahun === null ? "" : String(petaFilter.tahun));
      $("#petaPt").val(petaFilter.kodept || "");
      // Segarkan tampilan Select2 tanpa memicu handler change
      $("#petaTahun, #petaPt").trigger("change.select2");
    }

    function petaParams() {
      var params = {};
      if (petaFilter.tahun !== null) params.tahun = petaFilter.tahun;
      if (petaFilter.kodept) params.kodept = petaFilter.kodept;
      return params;
    }

    function petaSchedule() {
      petaSyncControls();
      clearTimeout(petaTimer);
      petaTimer = setTimeout(petaFetch, 250);
    }

    // Kosongkan marker & ringkasan agar data filter lama tidak tampil saat gagal
    function petaClear(message) {
      petaFocusCancel();
      petaMap.closePopup();
      petaLayer.clearLayers();
      petaMarkers = [];
      document.getElementById("petaSorot").length = 1;
      $("#petaSorot").trigger("change.select2");
      document.getElementById("petaTop").textContent = "";
      $("#petaTopEmpty").addClass("d-none");
      document.getElementById("petaKec").textContent = "";
      $("#petaKecPeriode").text("");
      $("#petaKecEmpty").addClass("d-none");
      $("#petaSubtitle").text("Data tidak tersedia");
      $("#petaTotal").text("–");
      $("#petaSummaryNote").text("");
      petaUnmapped(null);
      petaStatus(message, true);
    }

    // Nilai filter yang tidak lagi ada di options dikembalikan ke default
    function petaSanitize() {
      var years = petaList(petaOptions.tahun).map(function (y) { return parseInt(y, 10); });
      var ptIds = petaList(petaOptions.pt).map(function (p) { return String(p.kodept); });
      if (petaFilter.tahun !== null && years.indexOf(petaFilter.tahun) === -1) {
        petaFilter.tahun = parseInt(petaOptions.tahun_default, 10) || years[0] || null;
      }
      if (petaFilter.kodept && ptIds.indexOf(petaFilter.kodept) === -1) petaFilter.kodept = null;
    }

    // 422 = filter basi: muat ulang options sekali (tanpa cache), lalu fetch ulang
    function petaRefreshOptions(seq) {
      $("#petaMapWrap").attr("aria-busy", "true");
      $.ajax({ url: $("#petaSebaran").data("filter-url"), dataType: "json", cache: false })
        .done(function (opts) {
          if (seq !== petaSeq) return;
          petaOptions = opts || {};
          petaCache = {};
          petaBuildFilters();
          petaSanitize();
          petaSyncControls();
          petaFetch(true);
        })
        .fail(function () {
          if (seq === petaSeq) {
            $("#petaMapWrap").attr("aria-busy", "false");
            petaStatus("Data peta gagal dimuat. Silakan coba lagi.", true);
          }
        });
    }

    function petaFetch(staleRetried) {
      var params = petaParams();
      var key = $.param(params);
      // Respons lama yang datang terlambat diabaikan
      var seq = ++petaSeq;

      if (petaCache[key]) {
        $("#petaMapWrap").attr("aria-busy", "false");
        petaRender(petaCache[key]);
        return;
      }

      $("#petaMapWrap").attr("aria-busy", "true");
      $.getJSON($("#petaSebaran").data("url"), params)
        .done(function (res) {
          if (!res || !Array.isArray(res.desa)) {
            if (seq === petaSeq) petaClear("Data peta tidak valid. Silakan coba lagi.");
            return;
          }
          petaCache[key] = res;
          if (seq === petaSeq) petaRender(res);
        })
        .fail(function (xhr) {
          if (seq !== petaSeq) return;
          petaClear("Data peta gagal dimuat. Silakan coba lagi.");
          if (xhr.status === 422 && staleRetried !== true) {
            petaStatus("Memperbarui pilihan filter…", false);
            petaRefreshOptions(seq);
          }
        })
        .always(function (resOrXhr, textStatus) {
          // Saat options dimuat ulang, busy direset oleh petaRefreshOptions/petaFetch berikutnya
          var refreshing = textStatus !== "success" && resOrXhr && resOrXhr.status === 422 && staleRetried !== true;
          if (seq === petaSeq && !refreshing) $("#petaMapWrap").attr("aria-busy", "false");
        });
    }

    function petaRender(res) {
      var rows = res.desa;
      var order = [];

      petaFocusCancel();
      petaMap.closePopup();
      petaLayer.clearLayers();
      // Bounds tetap mencakup semua desa berkoordinat (termasuk tier 0)
      petaBounds = L.latLngBounds([]);
      petaMarkers = rows.map(function (row, index) {
        var lat = parseFloat(row.latitude);
        var lng = parseFloat(row.longitude);
        if (!isFinite(lat) || !isFinite(lng)) return null;
        petaBounds.extend([lat, lng]);

        var tier = parseInt(row.tier, 10);
        if (!(tier >= 0 && tier < petaTiers.length)) tier = 0;
        // Tier 0 (tanpa mahasiswa) tidak digambar
        if (tier === 0) return null;
        var marker = L.circleMarker([lat, lng], {
          radius: petaTiers[tier].radius,
          color: petaTiers[tier].stroke,
          weight: tier > 0 ? 1.75 : 1.25,
          opacity: tier > 0 ? 1 : 0.85,
          dashArray: tier > 0 ? null : "2 2",
          fillColor: "url(#petaGrad" + tier + ")",
          fillOpacity: tier > 0 ? 0.82 : 0.6,
          // Tier 0 (kosong) tanpa pulse
          className: tier > 0 ? "peta-dot peta-bubble" : "peta-dot"
        }).bindPopup(petaPopup(row, res.periode))
          // Lepas buka-popup bawaan klik; popup dibuka petaFocus setelah animasi
          .off("click")
          .on("click", function (e) {
            L.DomEvent.stopPropagation(e);
            petaFocus(this);
          })
          .on("popupopen popupclose", function (e) {
            var el = this.getElement();
            if (el) el.classList.toggle("is-active", e.type === "popupopen");
          });
        order.push({ marker: marker, tier: tier });
        return marker;
      });

      // Lingkaran besar digambar dulu agar yang kecil tetap bisa diklik
      order.sort(function (a, b) {
        return b.tier - a.tier;
      }).forEach(function (item) {
        petaLayer.addLayer(item.marker);
        // Delay acak agar detak tidak serempak
        var el = item.tier > 0 && item.marker.getElement();
        if (el) el.style.animationDelay = (Math.random() * 2).toFixed(2) + "s";
      });

      petaSummary(res);
      petaSide(rows, res.periode);
      petaKecamatan(res);

      if (order.length === 0) {
        var tahunRes = res.filter && res.filter.tahun ? res.filter.tahun : petaFilter.tahun;
        petaStatus(rows.length === 0 && res.mode === "pt"
          ? "PT ini belum memiliki mahasiswa di tahun " + tahunRes
          : "Belum ada kelurahan/desa dengan mahasiswa untuk filter ini.", false);
        return;
      }
      petaStatus("", false);
      petaFit();
    }

    function petaFit() {
      petaMap.fitBounds(petaBounds && petaBounds.isValid() ? petaBounds : petaLayer.getBounds(), { padding: [30, 30], maxZoom: 14, animate: !petaReduceMotion });
      // Container berukuran 0 (belum ter-layout) ditandai agar fit bisa diulang
      petaFitted = document.getElementById("petaSebaran").offsetWidth > 0;
    }

    function petaSummary(res) {
      var summary = res.summary || {};
      var tahun = res.filter && res.filter.tahun ? res.filter.tahun : petaFilter.tahun;
      var ptMode = res.mode === "pt";
      var note = "mahasiswa KKN" + (ptMode ? " · " + $("#petaPt option:selected").text() : "");
      if (summary.desa_tersamar > 0) {
        note += ". " + summary.desa_tersamar + " kelurahan/desa dengan <3 mahasiswa disamarkan.";
      }

      $("#petaSubtitle").text("Jelajahi sebaran mahasiswa KKN Tematik Sampah Tuntas di 73 kelurahan/desa pada 32 kecamatan. Pantau lokasi penugasan, jumlah mahasiswa, dan capaian pengurangan sampah di setiap wilayah.");
      $("#petaSummaryBadge").text(tahun ? "Tahun " + tahun : "Semua Tahun");
      $("#petaTotal").text(summary.total_label == null ? "0" : summary.total_label);
      $("#petaSummaryNote").text(note);
      petaUnmapped(summary);
    }

    // Desa ber-data tanpa koordinat; disembunyikan bila 0/field tidak ada
    function petaUnmapped(summary) {
      var box = document.getElementById("petaUnmapped");
      var list = document.getElementById("petaUnmappedList");
      var rows = petaList(summary && summary.belum_dipetakan);
      var total = parseInt(summary && summary.jumlah_belum_dipetakan, 10) || 0;
      list.textContent = "";
      box.open = false;
      box.classList.toggle("d-none", total <= 0);
      if (total <= 0) return;
      document.getElementById("petaUnmappedText").textContent =
        total + " kelurahan/desa belum dipetakan (koordinat belum diisi)";
      rows.forEach(function (row) {
        var li = petaEl("li", null, row && row.desa ? row.desa : "-");
        if (row && row.kecamatan) li.appendChild(petaEl("span", "peta-muted", " — " + row.kecamatan));
        list.appendChild(li);
      });
    }

    function petaSide(rows, periode) {
      var sorot = document.getElementById("petaSorot");
      var list = document.getElementById("petaTop");

      sorot.length = 1;
      rows.forEach(function (row, index) {
        if (petaMarkers[index]) {
          sorot.add(new Option(row.desa + (row.kecamatan ? " (" + row.kecamatan + ")" : ""), String(index)));
        }
      });

      var top = rows.map(function (row, index) {
        return { row: row, index: index };
      }).filter(function (item) {
        item.persen = petaPersen(item.row.persen_pengurangan);
        return petaMarkers[item.index] && item.persen !== null;
      }).sort(function (a, b) {
        // Persen DESC, lalu nama desa
        return b.persen - a.persen || a.row.desa.localeCompare(b.row.desa, "id");
      }).slice(0, 5);

      list.textContent = "";
      top.forEach(function (item, rank) {
        var li = petaEl("li");
        var btn = petaEl("button", "peta-top-item");
        var body = petaEl("span", "peta-top-body");
        var count = petaEl("span", "text-end");

        btn.type = "button";
        btn.setAttribute("data-index", String(item.index));
        btn.setAttribute("aria-label", "Sorot " + item.row.desa + ", pengurangan sampah " + petaPersenText(item.persen));
        btn.appendChild(petaEl("span", "peta-rank" + (rank < 3 ? " peta-rank-" + (rank + 1) : ""), "#" + (rank + 1)));
        body.appendChild(petaEl("span", "peta-top-name", item.row.desa));
        if (item.row.kecamatan) {
          body.appendChild(petaEl("span", "badge rounded-pill bg-label-primary mt-1", item.row.kecamatan));
        }
        body.appendChild(petaEl("span", "peta-top-reduksi", item.row.jumlah_label + " mahasiswa"));
        btn.appendChild(body);
        count.appendChild(petaEl("span", "peta-top-count d-block", petaPersenText(item.persen)));
        count.appendChild(petaEl("small", "peta-muted", periode && periode.label ? periode.label : "pengurangan"));
        btn.appendChild(count);
        li.appendChild(btn);
        list.appendChild(li);
      });
      $("#petaTopEmpty")
        .text("Belum ada data pengurangan sampah untuk filter ini.")
        .toggleClass("d-none", top.length > 0);
    }

    function petaKecamatan(res) {
      var list = document.getElementById("petaKec");
      var items = petaList(res.kecamatan);

      list.textContent = "";
      $("#petaKecPeriode").text(res.periode && res.periode.label ? "Periode " + res.periode.label : "");
      items.forEach(function (item) {
        var persen = petaPersen(item.persen_pengurangan);
        var li = petaEl("li");
        var head = petaEl("div", "peta-kec-row");
        var bar = petaEl("div", "peta-kec-bar");

        head.appendChild(petaEl("span", "peta-kec-name", item.kecamatan));
        head.appendChild(petaEl("span", "peta-kec-value" + (persen === null ? " is-empty" : ""), persen === null ? "Belum ada data" : petaPersenText(persen)));
        li.appendChild(head);
        if (persen !== null) {
          // Klaster warna mengikuti laporan publik: >=20 hijau, >=0 kuning, <0 merah
          var fill = petaEl("div", "peta-kec-fill " + (persen >= 20 ? "is-high" : persen >= 0 ? "is-mid" : "is-low"));
          fill.style.width = (persen < 0 ? 4 : Math.min(100, persen)) + "%"; // negatif tetap terlihat merah
          bar.appendChild(fill);
        }
        bar.setAttribute("aria-hidden", "true");
        li.appendChild(bar);
        list.appendChild(li);
      });
      $("#petaKecEmpty").toggleClass("d-none", items.length > 0);
    }

    function petaFocusCancel() {
      if (petaFocusPending) petaMap.off("moveend", petaFocusPending);
      petaFocusPending = null;
    }

    // Dipakai Sorot Desa, Top 5, dan klik bubble (index atau marker)
    function petaFocus(target) {
      var marker = typeof target === "number" ? petaMarkers[target] : target;
      if (!marker) return;

      var latlng = marker.getLatLng();
      // Tidak pernah zoom out bila sudah lebih dekat
      var zoom = Math.max(petaMap.getZoom(), 14);
      petaFocusCancel();
      petaMap.closePopup();

      // Di mobile panel berada di bawah peta (klik bubble sudah di peta)
      if (typeof target === "number" && window.innerWidth < 992) {
        document.getElementById("petaSebaran").scrollIntoView({ behavior: petaReduceMotion ? "auto" : "smooth", block: "center" });
      }

      if (petaReduceMotion) {
        petaMap.setView(latlng, zoom, { animate: false });
        marker.openPopup();
        return;
      }
      // on/off manual (bukan once) agar bisa dibatalkan bersih saat klik beruntun
      petaFocusPending = function () {
        petaFocusCancel();
        if (petaLayer.hasLayer(marker)) marker.openPopup();
      };
      petaMap.on("moveend", petaFocusPending);
      petaMap.flyTo(latlng, zoom, { duration: 0.8 });
    }

    function petaReset() {
      var years = petaList(petaOptions.tahun);
      var year = parseInt(petaOptions.tahun_default, 10) || parseInt(years[0], 10) || null;
      petaFilter = { tahun: year, kodept: null };
      petaSyncControls();
      clearTimeout(petaTimer);
      petaFetch();
    }

    function petaBootFail() {
      petaBooting = false;
      petaStatus("Peta gagal dimuat. Silakan coba lagi.", true);
    }

    function petaBoot() {
      if (petaBooting) return;
      petaBooting = true;
      petaStatus("Memuat peta…", false);
      petaLoadLeaflet(function () {
        $.getJSON($("#petaSebaran").data("filter-url"))
          .done(function (opts) {
            petaBooting = false;
            petaOptions = opts || {};
            petaInitMap();
            petaBuildFilters();
            petaReset();
          })
          .fail(petaBootFail);
      }, petaBootFail);
    }

    // Peta di-boot sekali saat section mendekati viewport (hemat request & throttle)
    var petaEl0 = document.getElementById("petaSebaran");
    if (petaEl0) {
      if (hasIO) {
        var petaIO = new IntersectionObserver(function (entries) {
          if (entries.some(function (e) { return e.isIntersecting; })) {
            petaIO.disconnect();
            petaBoot();
          }
        }, { rootMargin: "300px 0px" });
        petaIO.observe(petaEl0);
      } else {
        petaBoot();
      }
    }

    $("#petaRetry").on("click", function () {
      if (petaMap) {
        petaFetch();
      } else {
        petaBoot();
      }
    });

    $("#petaTahun").on("change", function () {
      petaFilter.tahun = parseInt(this.value, 10) || null;
      petaSchedule();
    });

    $("#petaPt").on("change", function () {
      petaFilter.kodept = this.value || null;
      petaSchedule();
    });

    $("#petaReset").on("click", function () {
      if (petaOptions && petaMap) petaReset();
    });

    $("#petaSorot").on("change", function () {
      if (this.value === "") return;
      petaFocus(parseInt(this.value, 10));
      // Kembali ke placeholder agar desa yang sama bisa dipilih ulang
      this.value = "";
      $(this).trigger("change.select2");
    });

    $("#petaTop").on("click", ".peta-top-item", function () {
      petaFocus(parseInt($(this).attr("data-index"), 10));
    });
  });
})(jQuery);
