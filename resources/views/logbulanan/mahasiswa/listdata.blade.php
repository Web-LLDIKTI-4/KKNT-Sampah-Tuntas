<div class="col-12 table-responsive">
<table class="table table-bordered table-sm" id="dataTable">
    <thead>
        <tr>
            <th class="text-center" width="1">No</th>
            <th class="text-center">Tahun</th>
            <th class="text-center">Bulan</th>
            <th class="text-center">Tautan</th>
            <th class="text-center" width="1">Aksi</th>
            <th class="text-center">Nilai</th>
            <th class="text-center">Hasil Verifikasi</th>
        </tr>
    </thead>
    <tbody>
        @if($laporan->isEmpty())
            
        @else
            @foreach($laporan as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $row->tahun }}</td>
                    <td class="text-center">{{ Carbon\Carbon::create()->month($row->bulan)->translatedFormat('F') }}</td>
                    <td>
                        {!! \App\Support\HtmlSanitizer::link($row->tautan) !!}
                    </td>
                    <td class="text-center">
                        <x-action-data :urlDelete="url('logbulanan/destroy')" idField="id_logbulanan" :idValue="$row->id_logbulanan">
                            <form method="post" id="form-bulan-{{ $row->id_logbulanan }}" action="{{ url('logbulanan/tambah') }}" data-ajax-form data-result-target="#resultcontent">
                                @csrf
                                <input type="hidden" name="tahun" value="{{ $row->tahun }}">
                                <input type="hidden" name="bulan" value="{{ $row->bulan }}">
                                <x-button.icon action="edit" type="submit" id="btnSubmit_form-bulan-{{ $row->id_logbulanan }}" title="Ubah Data" />
                            </form>
                        </x-action-data>
                    </td>
                    <td class="text-center">{{$row->nilai}}</td>
                    <td>{{$row->hasil_verifikasi}}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
</div>
<script>
$(function(){
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
    });
})
</script>