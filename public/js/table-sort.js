/*
 * Sort per kolom di sisi klien untuk tabel statis.
 * - <table data-sortable>; header: <th><button type="button" class="table-sort" data-sort-type="text|num">Label<i class="ri-arrow-up-down-line" aria-hidden="true"></i></button></th>
 * - <td data-sort-value="..">: nilai sort (fallback teks sel). Kosong / "-" selalu di akhir.
 * - <tr data-sort-fixed>: baris tidak dipindah; memisah segmen yang di-sort sendiri-sendiri.
 * - <tr data-sort-follow>: ikut baris sebelumnya (mis. baris detail).
 * - <td data-sort-no>: dinomori ulang per segmen.
 * Klik: kolom baru asc, kolom sama toggle desc. Memicu event jQuery "table:sorted".
 */
(function () {
  "use strict";

  var collator = new Intl.Collator("id", { numeric: true, sensitivity: "base" });
  var ICONS = { asc: "ri-arrow-up-line", desc: "ri-arrow-down-line", none: "ri-arrow-up-down-line" };

  // Index kolom visual (memperhitungkan colspan)
  function colIndex(cell) {
    var idx = 0;
    for (var c = cell.parentNode.firstElementChild; c && c !== cell; c = c.nextElementSibling) {
      idx += c.colSpan || 1;
    }
    return idx;
  }

  function cellAt(row, col) {
    var idx = 0;
    for (var c = row.firstElementChild; c; c = c.nextElementSibling) {
      if (idx === col) return c;
      idx += c.colSpan || 1;
      if (idx > col) return null;
    }
    return null;
  }

  function readValue(row, col, isNum) {
    var cell = cellAt(row, col);
    if (!cell) return null;
    var raw = cell.hasAttribute("data-sort-value") ? cell.getAttribute("data-sort-value") : cell.textContent;
    raw = String(raw).trim();
    if (raw === "" || raw === "-") return null;
    if (!isNum) return raw;
    var num = Number(raw);
    return isNaN(num) ? null : num;
  }

  function sortBody(tbody, col, dir, isNum) {
    var rows = Array.prototype.slice.call(tbody.children);
    var out = [];
    var units = [];

    // Simpan urutan asli sekali agar nilai sama tetap stabil
    rows.forEach(function (row, i) {
      if (row._sortOrig === undefined) row._sortOrig = i;
    });

    var flush = function () {
      units.sort(function (a, b) {
        var x = a.value, y = b.value;
        if (x === null || y === null) {
          return x === y ? a.orig - b.orig : (x === null ? 1 : -1);
        }
        var cmp = isNum ? x - y : collator.compare(x, y);
        return cmp !== 0 ? dir * cmp : a.orig - b.orig;
      });
      units.forEach(function (unit, i) {
        var no = unit.rows[0].querySelector(":scope > [data-sort-no]");
        if (no) no.textContent = i + 1;
        out.push.apply(out, unit.rows);
      });
      units = [];
    };

    rows.forEach(function (row) {
      if (row.hasAttribute("data-sort-fixed")) {
        flush();
        out.push(row);
      } else if (row.hasAttribute("data-sort-follow") && units.length) {
        units[units.length - 1].rows.push(row);
      } else {
        units.push({ rows: [row], value: readValue(row, col, isNum), orig: row._sortOrig });
      }
    });
    flush();

    out.forEach(function (row) {
      tbody.appendChild(row);
    });
  }

  document.addEventListener("click", function (e) {
    var btn = e.target.closest ? e.target.closest(".table-sort") : null;
    var th = btn ? btn.closest("th") : null;
    var table = th ? th.closest("table") : null;
    if (!table || !table.hasAttribute("data-sortable")) return;

    var col = colIndex(th);
    var state = table._sortState || { col: null, dir: 1 };
    state.dir = state.col === col ? -state.dir : 1;
    state.col = col;
    table._sortState = state;

    var isNum = btn.getAttribute("data-sort-type") === "num";
    Array.prototype.forEach.call(table.tBodies, function (tbody) {
      sortBody(tbody, col, state.dir, isNum);
    });

    if (table.tHead) {
      Array.prototype.forEach.call(table.tHead.querySelectorAll(".table-sort"), function (b) {
        var cell = b.closest("th");
        var active = cell === th;
        if (active) {
          cell.setAttribute("aria-sort", state.dir === 1 ? "ascending" : "descending");
        } else {
          cell.removeAttribute("aria-sort");
        }
        var icon = b.querySelector("i");
        if (icon) icon.className = active ? (state.dir === 1 ? ICONS.asc : ICONS.desc) : ICONS.none;
      });
    }

    if (window.jQuery) window.jQuery(table).trigger("table:sorted", [col, state.dir]);
  });
})();
