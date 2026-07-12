<div class="col-12 table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                @foreach ($bulan as $angka => $nama)
                    <th>{{ $nama }} ({{ $angka }})</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($groupedData as $month => $records)
                @foreach ($records as $index => $item)
                    <tr>
                        @foreach ($bulan as $angka => $nama)
                            <td>{{ $month == $angka ? $item->nilai : '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
