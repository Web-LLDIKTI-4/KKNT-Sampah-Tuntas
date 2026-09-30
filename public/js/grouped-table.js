/**
 * Limit, pencarian, dan pagination untuk tabel dengan sel merge.
 * - <tr data-group="key">: baris dengan key sama dihitung satu data (tidak terpotong antarhalaman).
 * - <td data-merge="key">: sel berurutan dengan key sama di kolom yang sama digabung (rowspan) setelah paging.
 */
window.GroupedTable = (function () {
  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function pageList(page, pages) {
    var list = [];
    for (var i = 1; i <= pages; i++) {
      if (i === 1 || i === pages || Math.abs(i - page) <= 1) {
        list.push(i);
      } else if (list[list.length - 1] !== '…') {
        list.push('…');
      }
    }
    return list;
  }

  function merge(rows) {
    var runs = {};
    rows.forEach(function (row) {
      row.querySelectorAll('td[data-merge]').forEach(function (cell, idx) {
        var run = runs[idx];
        if (run && run.key === cell.dataset.merge) {
          run.cell.rowSpan += 1;
          cell.style.display = 'none';
        } else {
          cell.rowSpan = 1;
          cell.style.display = '';
          runs[idx] = { key: cell.dataset.merge, cell: cell };
        }
      });
    });
  }

  function init(table) {
    if (!table || table.dataset.groupedInit) return;
    var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-group]'));
    if (!rows.length) return;
    table.dataset.groupedInit = '1';

    var label = table.dataset.groupLabel || 'data';
    var units = [];
    var byKey = {};
    rows.forEach(function (row) {
      var key = row.dataset.group;
      if (!byKey[key]) {
        byKey[key] = { rows: [], text: '' };
        units.push(byKey[key]);
      }
      byKey[key].rows.push(row);
      byKey[key].text += ' ' + row.textContent.toLowerCase();
    });

    var state = { page: 1, length: 10, keyword: '' };
    var anchor = table.closest('.table-responsive') || table;

    var top = el('div', 'd-flex flex-wrap justify-content-between align-items-center gap-3 mb-3');
    var lengthLabel = el('label', 'd-flex align-items-center gap-2 mb-0 small');
    var select = el('select', 'form-select form-select-sm w-auto');
    [10, 25, 50, 100].forEach(function (n) {
      var opt = el('option', '', String(n));
      opt.value = n;
      select.appendChild(opt);
    });
    lengthLabel.appendChild(document.createTextNode('Tampilkan'));
    lengthLabel.appendChild(select);
    lengthLabel.appendChild(document.createTextNode(label));
    var search = el('input', 'form-control form-control-sm w-auto');
    search.type = 'search';
    search.placeholder = 'Cari...';
    search.setAttribute('aria-label', 'Cari ' + label);
    top.appendChild(lengthLabel);
    top.appendChild(search);

    var bottom = el('div', 'd-flex flex-wrap justify-content-between align-items-center gap-3 mt-3');
    var info = el('div', 'small text-muted');
    var nav = el('nav');
    nav.setAttribute('aria-label', 'Halaman ' + label);
    var pager = el('ul', 'pagination pagination-sm mb-0');
    nav.appendChild(pager);
    bottom.appendChild(info);
    bottom.appendChild(nav);

    var emptyBody = el('tbody');
    var emptyCell = el('td', 'text-center text-muted', 'Tidak ada data yang ditemukan');
    emptyCell.colSpan = table.querySelectorAll('thead th').length;
    emptyBody.appendChild(el('tr')).appendChild(emptyCell);
    table.appendChild(emptyBody);

    anchor.parentNode.insertBefore(top, anchor);
    anchor.parentNode.insertBefore(bottom, anchor.nextSibling);

    function pageItem(text, page, disabled, active) {
      var li = el('li', 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : ''));
      var btn = el('button', 'page-link', text);
      btn.type = 'button';
      if (page && !disabled) btn.dataset.page = page;
      if (active) btn.setAttribute('aria-current', 'page');
      li.appendChild(btn);
      pager.appendChild(li);
    }

    function render() {
      var matched = units.filter(function (u) {
        return !state.keyword || u.text.indexOf(state.keyword) !== -1;
      });
      var pages = Math.max(1, Math.ceil(matched.length / state.length));
      state.page = Math.min(state.page, pages);
      var start = (state.page - 1) * state.length;
      var shown = matched.slice(start, start + state.length);

      rows.forEach(function (row) { row.style.display = 'none'; });
      var visible = [];
      shown.forEach(function (u) {
        u.rows.forEach(function (row) {
          row.style.display = '';
          visible.push(row);
        });
      });
      merge(visible);
      emptyBody.style.display = matched.length ? 'none' : '';

      info.textContent = matched.length
        ? 'Menampilkan ' + (start + 1) + '–' + (start + shown.length) + ' dari ' + matched.length + ' ' + label
          + (matched.length < units.length ? ' (disaring dari ' + units.length + ')' : '')
        : 'Tidak ada ' + label;

      pager.innerHTML = '';
      pageItem('‹', state.page - 1, state.page === 1);
      pageList(state.page, pages).forEach(function (p) {
        if (p === '…') {
          pageItem(p, null, true);
        } else {
          pageItem(String(p), p, false, p === state.page);
        }
      });
      pageItem('›', state.page + 1, state.page === pages);
    }

    select.addEventListener('change', function () {
      state.length = parseInt(select.value, 10);
      state.page = 1;
      render();
    });
    search.addEventListener('input', function () {
      state.keyword = search.value.toLowerCase().trim();
      state.page = 1;
      render();
    });
    pager.addEventListener('click', function (e) {
      var btn = e.target.closest('button[data-page]');
      if (!btn) return;
      state.page = parseInt(btn.dataset.page, 10);
      render();
    });

    render();
  }

  return { init: init };
})();
