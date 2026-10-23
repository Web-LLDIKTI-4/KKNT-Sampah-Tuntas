<div class="table-responsive">
<table class="table table-sm table-striped" data-sortable>
    <thead>
        <tr>
            <th width="1%">No</th>
            <th><x-button.sort>Free Form</x-button.sort></th>
            <th><x-button.sort type="num">Nilai DPL</x-button.sort></th>
            {{-- <th>Nilai DPA</th> --}}
            <th><x-button.sort type="num">Nilai Akhir</x-button.sort></th>
        </tr>
    </thead>
    <tbody>
        @if($nilai->isEmpty())
            <tr data-sort-fixed>
                <td colspan="5">Tidak ada data yang tersedia</td>
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
                    <td class="text-wrap">{{$item->freeform}}</td>
                    <td class="text-center">{{$item->nilai_dpl}}</td>
                    {{-- <td class="text-center">{{$item->nilai_dpa}}</td> --}}
                    <td class="text-center" data-sort-value="{{ $nilaiakhir }}">{{ $nilaiakhir }} - {{ $grade }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
</div>