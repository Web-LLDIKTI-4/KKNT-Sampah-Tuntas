<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th class="text-center">Bulan</th>
                    {{-- <th>Id Capaian</th> --}}
                    <th>Lokasi Kegiatan</th>
                    <th>Ketua Kelompok</th>
                    <th>Kategori Kegiatan</th>
                    <th>Permasalahan</th>
                    <th>Solusi</th>
                    <th>Kebutuhan Dukungan</th>
                    <th>Tindak Lanjut</th>
                    <th>Tautan</th>
                    {{-- <th width="1">Aksi</th> --}}
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-button.export url="{{ url('lapcapaiankegiatan/export') }}" />

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
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('lapcapaiankegiatan.listdataserver') }}",
        order: [[1, 'desc']],
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex' , className: 'text-center', orderable: false, searchable: false},
            {data: 'bulan_label', name: 'bulan', className: 'text-center', orderable: true, searchable: false},
            // {data: 'id_target', name: 'id_target', visible:false},
            {data: 'lokasi', name: 'lokasi', orderable: false, searchable: false},
            {data: 'pjdesa', name: 'pjdesa'},
            {data: 'nama_kategori', name: 'nama_kategori'},
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