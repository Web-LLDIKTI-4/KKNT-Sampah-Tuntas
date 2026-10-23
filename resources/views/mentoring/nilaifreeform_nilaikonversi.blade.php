<div class="table-responsive">
<table class="table table-sm table-bordered" data-sortable>
    <thead>
        <tr>
            <th width="1%">No</th>
            <th><x-button.sort>Matakuliah</x-button.sort></th>
            <th><x-button.sort type="num">SKS</x-button.sort></th>
            <th><x-button.sort type="num">Nilai DPL</x-button.sort></th>
            <th><x-button.sort type="num">Nilai DPA</x-button.sort></th>
            <th><x-button.sort type="num">Nilai Akhir</x-button.sort></th>
        </tr>
    </thead>
    <tbody>
        @if($nilai->isEmpty())
            <tr data-sort-fixed>
                <td colspan="6">Tidak ada data yang tersedia</td>
            </tr>
        @else
            @foreach($nilai as $item)
                @php
                    $nilaiakhir = ($item->nilai_dpl+$item->nilai_dpa)/2;
                    if ($nilaiakhir >= 80) {
                        $grade = 'A';
                    } elseif ($nilaiakhir < 70) {
                        $grade = 'C';
                    } else {
                        $grade = 'B';
                    }
                @endphp
                <tr>
                    <td class="text-center" data-sort-no>{{$loop->iteration}}</td>
                    <td class="text-wrap">{{$item->matakuliah}}</td>
                    <td class="text-center">{{$item->sks}}</td>
                    <td class="text-center">{{$item->nilai_dpl}}</td>
                    <td class="text-center">{{$item->nilai_dpa}}</td>
                    <td class="text-center" data-sort-value="{{ $nilaiakhir }}">{{ $nilaiakhir }} - {{ $grade }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
</div>