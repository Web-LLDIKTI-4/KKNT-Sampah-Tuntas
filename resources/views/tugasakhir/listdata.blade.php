<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tautan</th>
                    <th>Nilai</th>
                    <th width="1">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @if($data->isEmpty())
                    
                @else
                    @foreach($data as $item)
                    <tr>
                        <td class="text-center">{{$loop->iteration}}</td>
                        <td><a href="{{$item->tautan}}" target="_blank">{{$item->tautan}}</a></td>
                        <td class="text-center">{{ $item->nilai_dpl }}</td>
                        <td class="text-center d-flex justify-content-center">
                            {{-- <x-action-data urlEdit="{{ url('tugasakhir/formpenilaian/'.$item->id_tugasakhir) }}" urlDelete="{{ url('tugasakhir/destroy') }}" /> --}}
                            <x-btn-edit url="{{ url('tugasakhir/edit/'.$item->id_tugasakhir) }}" />
                            <x-btn-delete url="{{ url('tugasakhir/destroy') }}" idField="id_tugasakhir" :idValue="$item->id_tugasakhir" />
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
    });
  });
</script>