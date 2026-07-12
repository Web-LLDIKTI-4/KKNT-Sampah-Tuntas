<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tautan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @if($data->isEmpty())
                   
                @else
                    @foreach($data as $item)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td><a href="{{$item->tautan}}" target="_blank">{{$item->tautan}}</a></td>
                        <td>
                            <a class="modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('tugasakhir/edit/'.$item->id_tugasakhir) }}" title="Edit Data">Edit</a>
                            <a>Hapus</a>
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
    $('#dataTable').DataTable();
  });
</script>