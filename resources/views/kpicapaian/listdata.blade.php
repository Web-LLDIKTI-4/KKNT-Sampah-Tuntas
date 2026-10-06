<div class="row">
    <div class="col-12">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th class="text-center">Bulan</th>
                    <th>Id kpicapaian</th>
                    <th>Lokasi Kegiatan</th>
                    <th>Nama KPI</th>
                    <th>Permasalahan</th>
                    <th>Solusi</th>
                    <th>Kebutuhan Dukungan</th>
                    <th>Tindak Lanjut</th>
                    <th>Tautan</th>
                    @if ($isKetua ?? false)
                        <th width="1">Aksi</th>
                    @endif
                </tr>
            </x-slot:thead>
        </x-datatable>

        <x-button.export url="{{ url('kpicapaian/export') }}" />
    </div>
</div>
<script type="text/javascript">
  $(function () {
  // Teks dari HTML via DOMParser (inert); <br> jadi baris baru, output di-escape lewat .text()
  function multilineText(data) {
    var body = new DOMParser().parseFromString(data || '', 'text/html').body;
    body.querySelectorAll('br').forEach(function (br) {
      var next = br.nextSibling;
      // nl2br sudah menyisakan "\n" setelah <br>; jangan digandakan
      if (next && next.nodeType === 3 && /^\r?\n/.test(next.nodeValue)) {
        br.parentNode.removeChild(br);
      } else {
        br.parentNode.replaceChild(document.createTextNode('\n'), br);
      }
    });
    return $('<div class="text-wrap text-preline"></div>').text(body.textContent || '').prop('outerHTML');
  }

    var table = $('#dataTable').DataTable({
        seaching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        scrollX: true,
        order: [[1, 'desc']],
        ajax: "{{ route('kpicapaian.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'bulan_label', name: 'bulan', className: 'text-center', orderable: true, searchable: false},
            {data: 'id_capaian', name: 'id_capaian', visible:false},
            {data: 'lokasi', name: 'lokasi'},
            {data: 'nama_kpi', name: 'nama_kpi'},
            {
                data: 'permasalahan',
                name: 'permasalahan',
                render: function (data, type, row) {
                    return multilineText(data);
                }
            },
            {
                data: 'solusi',
                name: 'solusi',
                render: function (data, type, row) {
                    return multilineText(data);
                }
            },
            {
                data: 'kendala',
                name: 'kendala',
                render: function (data, type, row) {
                    return multilineText(data);
                }
            },
            {data: 'status_capaian', name: 'status_capaian', className: 'text-center'},
            {data: 'tautan', name: 'tautan'},
            @if ($isKetua ?? false)
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            @endif
        ],
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        }
    });
  });
</script>