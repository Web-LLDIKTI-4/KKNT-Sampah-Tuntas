/*
 * Semua <select> otomatis menjadi select dengan pencarian (Select2).
 * - Kecualikan dengan atribut data-no-search.
 * - Dropdown ditempel ke induk select agar tidak memicu scroll horizontal halaman
 *   dan tetap berfungsi di dalam modal.
 * - Konten yang dimuat lewat AJAX (modal, listdata) ikut diinisialisasi.
 */
(function ($) {
    'use strict';

    var SKIP = '[data-no-search], .select2-hidden-accessible, .dataTables_length select';

    function init(root) {
        $(root || document).find('select').not(SKIP).each(function () {
            var $select = $(this);
            var $parent = $select.parent().addClass('position-relative');

            $select.select2({
                width: '100%',
                dropdownParent: $parent,
                language: {
                    noResults: function () { return 'Data tidak ditemukan'; },
                    searching: function () { return 'Mencari...'; }
                }
            });
        });
    }

    window.initSearchSelect = init;

    $(function () { init(); });
    $(document).ajaxComplete(function () { init(); });
})(jQuery);
