<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th width="1%">No</th>
            <th>Mata Kuliah</th>
            <th>SKS</th>
            <th>Nilai DPL</th>
            {{-- <th>Nilai DPA</th> --}}
            <th>Nilai Akhir</th>
        </tr>
    </thead>
    <tbody>
        @if($nilai->isEmpty())
            <tr>
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
                    <td>{{$loop->iteration}}</td>
                    <td>{{$item->matakuliah}}</td>
                    <td class="text-center">{{$item->sks}}</td>
                    <td class="text-center">{{$item->nilai_dpl}}</td>
                    {{-- <td class="text-center">{{$item->nilai_dpa}}</td> --}}
                    <td class="text-center">{{ $nilaiakhir }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>