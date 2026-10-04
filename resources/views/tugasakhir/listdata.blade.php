<div class="row">
    <div class="col-12 table-responsive">
        <x-table client-side table-class="table table-bordered" thead-class="">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tautan</th>
                    <th>Nilai</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
                @if($data->isEmpty())
                    
                @else
                    @foreach($data as $item)
                    <tr>
                        <td class="text-center">{{$loop->iteration}}</td>
                        <td><a href="{{$item->tautan}}" target="_blank">{{$item->tautan}}</a></td>
                        <td class="text-center">{{ $item->nilai_dpl }}</td>
                        <td>
                            <x-action-data :urlEdit="url('tugasakhir/edit/'.$item->id_tugasakhir)" :urlDelete="url('tugasakhir/destroy')" idField="id_tugasakhir" :idValue="$item->id_tugasakhir" />
                        </td>
                    </tr>
                    @endforeach
                @endif
        </x-table>
    </div>
</div>
