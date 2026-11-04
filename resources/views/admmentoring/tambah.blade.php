@if ($dpls->isEmpty())
    <div class="alert alert-warning mb-0" role="alert">
        Belum ada DPL yang memiliki perguruan tinggi. Lengkapi perguruan tinggi di Data DPL terlebih dahulu.
    </div>
@else
    <div class="row mb-3">
        <div class="col-12 col-lg-6">
            <label for="selectDpl" class="form-label">Pilih DPL</label>
            <select id="selectDpl" class="form-select form-select-sm">
                <option value="">-- Pilih DPL --</option>
                @foreach ($dpls as $dpl)
                    <option value="{{ $dpl->id_dpl }}">{{ $dpl->nama }} – {{ $dpl->sp->nm_lemb ?? $dpl->kodept }}</option>
                @endforeach
            </select>
            <div class="form-text">Hanya mahasiswa dari perguruan tinggi yang sama dan belum memiliki DPL yang ditampilkan.</div>
        </div>
    </div>

    <div id="kandidatContent">
        <div class="text-muted text-center py-5 border rounded">
            <i class="ri-user-search-line ri-30px d-block mb-2"></i>
            Pilih DPL untuk menampilkan kandidat mahasiswa.
        </div>
    </div>

    <script>
    $(function () {
        var emptyHtml = $('#kandidatContent').html();
        var baseUrl = "{{ url('admmentoring/kandidat') }}";

        $('#selectDpl').off('change.admmentoring').on('change.admmentoring', function () {
            var id = $(this).val();
            if (!id) {
                // Drop pending request and old table, then show the empty hint
                var $content = $('#kandidatContent');
                var xhr = $content.data('partial-xhr');
                if (xhr) xhr.abort();
                $content.find('table').each(function () {
                    if ($.fn.DataTable.isDataTable(this)) $(this).DataTable().destroy();
                });
                $content.html(emptyHtml);
                return;
            }
            loadPartial('#kandidatContent', baseUrl + '/' + encodeURIComponent(id), {
                loadingText: 'Memuat kandidat mahasiswa...',
                keepSearch: false
            });
        });
    });
    </script>
@endif
