<div class="col-12 table-responsive">
<x-table client-side thead-class="" :columns="[['orderable' => false], null, null, null, null]">
    <x-slot:thead>
        <tr>
            <th width="1">No</th><th>Tahun</th><th>Bulan</th><th>Tautan</th><th width="1">Aksi</th>
        </tr>
    </x-slot:thead>
        @if($laporan->isEmpty())
            
        @else
            @foreach($laporan as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $row->tahun }}</td>
                    <td class="text-center">{{ Carbon\Carbon::create()->month($row->bulan)->translatedFormat('F') }}</td>
                    <td>
                        {!! \App\Support\HtmlSanitizer::link($row->tautan) !!}
                    </td>
                    <td class="text-center no-sort">
                        <x-action-data :urlDelete="url('dpllaporan/destroy')" idField="id_laporan" :idValue="$row->id_laporan">
                            <form method="post" id="form-bulan-{{ $row->id_laporan }}" action="{{ url('dpllaporan/tambah') }}" data-ajax-form data-result-target="#resultcontent">
                                @csrf
                                <input type="hidden" name="tahun" value="{{ $row->tahun }}">
                                <input type="hidden" name="bulan" value="{{ $row->bulan }}">
                                <x-button.icon action="edit" type="submit" id="btnSubmit_form-bulan-{{ $row->id_laporan }}" title="Ubah Data" />
                            </form>
                        </x-action-data>
                    </td>
                </tr>
            @endforeach
        @endif
</x-table>
</div>
