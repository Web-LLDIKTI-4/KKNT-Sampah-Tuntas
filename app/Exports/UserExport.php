<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class UserExport
{
    private int $rowNumber = 0;

    public function __construct(private array $filters = []) {}

    // Filter sama dengan User::scopeManaged(), diproses per chunk agar hemat memori
    public function query(): Builder
    {
        $kodept = $this->filters['kodept'] ?? null;

        return DB::table('users as u')
            // MAX(id) per email: cegah baris ganda bila email master tidak unik
            ->leftJoin('mahasiswa as m', fn (JoinClause $j) => $j->on('m.email', '=', 'u.email')
                ->where('u.role', 'mahasiswa')
                ->whereRaw('m.id_mahasiswa = (select max(m2.id_mahasiswa) from mahasiswa m2 where m2.email = u.email)'))
            ->leftJoin('dpl as d', fn (JoinClause $j) => $j->on('d.email', '=', 'u.email')
                ->where('u.role', 'dpl')
                ->whereRaw('d.id_dpl = (select max(d2.id_dpl) from dpl d2 where d2.email = u.email)'))
            ->leftJoin('ref_satuanpendidikan as sp', 'sp.npsn', '=', DB::raw("(case u.role when 'mahasiswa' then m.kodept when 'dpl' then d.kodept else u.email end)"))
            ->whereIn('u.role', ['mahasiswa', 'dpl', 'pt', 'kepala', 'pemda'])
            ->when($this->filters['role'] ?? null, fn ($q, $role) => $q->where('u.role', $role))
            ->when($kodept, fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('u.role', 'mahasiswa')->whereIn('u.email', DB::table('mahasiswa')->select('email')->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('u.role', 'dpl')->whereIn('u.email', DB::table('dpl')->select('email')->where('kodept', $kodept)))
                ->orWhere(fn ($q) => $q->where('u.role', 'pt')->where('u.email', $kodept))))
            ->select([
                'u.id',
                'u.name',
                'u.email',
                'u.role',
                DB::raw("(case u.role when 'mahasiswa' then m.nim when 'dpl' then d.nidn else sp.npsn end) as nomor_induk"),
                'sp.nm_lemb',
            ]);
    }

    // Streams XLSX row by row so memory stays constant
    public function writeTo(string $path): void
    {
        $this->rowNumber = 0;
        // Per-export temp folder, removed even if the export fails
        $dir = storage_path('app/tmp/user-export-'.Str::uuid());
        File::ensureDirectoryExists($dir, 0700);

        try {
            $options = new Options;
            $options->setTempFolder($dir);
            $writer = new Writer($options);
            $writer->openToFile($path);
            $writer->addRow(new Row($this->cells($this->headings()), (new Style)->setFontBold()));

            foreach ($this->query()->lazyById(1000, 'u.id', 'id') as $row) {
                $writer->addRow(new Row($this->cells($this->map($row))));
            }

            $writer->close();
        } finally {
            File::deleteDirectory($dir);
        }
    }

    // Force text into StringCell so user input is never written as a formula
    private function cells(array $values): array
    {
        return array_map(fn ($value): Cell => is_int($value)
            ? new NumericCell($value, null)
            : new StringCell((string) $value, null), array_values($values));
    }

    public function map($row): array
    {
        return [
            'No' => ++$this->rowNumber,
            'Nama' => $row->name,
            'NIM/NIDN/Kode PT' => $row->nomor_induk,
            'Email' => $row->email,
            'Perguruan Tinggi' => $row->nm_lemb,
            'Peran' => ucfirst($row->role),
        ];
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nama',
            'NIM/NIDN/Kode PT',
            'Email',
            'Perguruan Tinggi',
            'Peran',
        ];
    }
}
