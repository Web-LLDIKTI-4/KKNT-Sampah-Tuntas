<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th width="1%">No</th>
            <th>Free Form</th>
            <th>Nilai DPL</th>
            <th>Nilai DPA</th>
            <th>Nilai Akhir</th>
        </tr>
    </thead>
    <tbody>
        @if($nilai->isEmpty())
            <tr>
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
                    <td>{{$loop->iteration}}</td>
                    <td>{{$item->freeform}}</td>
                    <td>{{$item->nilai_dpl}}</td>
                    <td>{{$item->nilai_dpa}}</td>
                    <td>{{ $nilaiakhir }} - {{ $grade }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>