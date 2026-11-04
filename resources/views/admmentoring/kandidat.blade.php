<form method="post" id="form-admmentoring" action="{{ route('admmentoring.insert') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_dpl" value="{{ $dpl->id_dpl }}">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="fw-semibold">{{ $dpl->nama }}</div>
            <small class="text-muted">{{ $dpl->sp->nm_lemb ?? ($dpl->kodept ?: 'Perguruan tinggi belum diisi') }}</small>
        </div>
        @unless ($kodeptMissing || $data->isEmpty())
            <x-button.save formId="form-admmentoring" icon="ri-user-add-fill">
                Tugaskan <span class="badge bg-white text-primary ms-1" id="kandidatCount">0</span>
            </x-button.save>
        @endunless
    </div>

    @if ($kodeptMissing)
        <div class="alert alert-warning mb-0" role="alert">DPL belum memiliki perguruan tinggi di Data DPL</div>
    @elseif ($data->isEmpty())
        <div class="alert alert-info mb-0" role="alert">
            Tidak ada kandidat. Semua mahasiswa di perguruan tinggi ini sudah memiliki DPL.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm" id="tabelKandidat">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Nim</th>
                        <th class="text-center">Nama</th>
                        <th class="text-center">Prodi</th>
                        <th class="text-center">Lokasi Program KKN</th>
                        <th class="no-sort text-center">
                            <label for="checkAllKandidat" class="d-block mb-1">Pilih</label>
                            <input type="checkbox" id="checkAllKandidat" class="form-check-input" title="Pilih semua hasil pencarian">
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $mahasiswa)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $mahasiswa->nim }}</td>
                            <td>{{ $mahasiswa->nama }}</td>
                            <td>{{ $mahasiswa->prodi ?? '-' }}</td>
                            <td class="text-center">{{ $mahasiswa->locationProgram->nama_lokasi ?? 'Belum Terdata' }}</td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" name="createuser[]" value="{{ $mahasiswa->email }}"
                                    aria-label="Pilih {{ $mahasiswa->nama }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</form>

@unless ($kodeptMissing || $data->isEmpty())
<script>
$(function () {
    var $form = $('#form-admmentoring');
    var $btn = $('#btnSubmit_form-admmentoring');
    var table = $('#tabelKandidat').DataTable({
        paging: true,
        searching: true,
        lengthChange: true,
        ordering: true,
        info: true,
        autoWidth: false,
        serverSide: false,
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang ditemukan",
            infoEmpty: "Tidak ada data yang tersedia",
            emptyTable: "Tidak ada data yang tersedia di tabel"
        },
        columnDefs: [
            { targets: 'no-sort', orderable: false }
        ],
    });

    // Checked emails across all pages, unique
    function checkedEmails() {
        var emails = [];
        table.$("input[name='createuser[]']:checked").each(function () {
            var value = $(this).val();
            if (value && emails.indexOf(value) === -1) emails.push(value);
        });
        return emails;
    }

    function refreshCount() {
        var count = checkedEmails().length;
        $('#kandidatCount').text(count);
        if (!$btn.data('btn-loading')) $btn.prop('disabled', count === 0);
    }

    refreshCount();

    // Bound to elements replaced on every partial load, so no duplicate handlers
    $('#checkAllKandidat').on('change', function () {
        table.$("input[name='createuser[]']", { search: 'applied' }).prop('checked', $(this).prop('checked'));
        refreshCount();
    });
    $('#tabelKandidat tbody').on('change', "input[name='createuser[]']", refreshCount);

    $form.on('submit', function (e) {
        e.preventDefault();
        var emails = checkedEmails();
        if (!emails.length) {
            toastr.warning('Pilih minimal satu mahasiswa.');
            return;
        }

        // Send as one comma-separated field to avoid PHP max_input_vars truncation
        var formData = $form.serializeArray().filter(function (item) {
            return item.name !== 'createuser[]';
        });
        formData.push({ name: 'createuser', value: emails.join(',') });

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            beforeSend: function () {
                btnLoading($btn, true);
            },
            complete: function () {
                btnLoading($btn, false);
                refreshCount();
            },
            success: function (data) {
                if ($.isEmptyObject(data.error)) {
                    $('#modalku').modal('hide');
                    toastr.success(data.success);
                    if ($.fn.DataTable.isDataTable('#dataTable')) {
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    }
                } else {
                    toastr.error(data.error);
                }
            },
            error: function (xhr) {
                if (xhr.status === 401 || xhr.status === 419) {
                    location.reload();
                    return;
                }
                toastr.error(xhr.status === 403 ? 'Anda tidak memiliki akses.' : 'Terjadi kesalahan, silakan coba lagi.');
            }
        });
    });
});
</script>
@endunless
