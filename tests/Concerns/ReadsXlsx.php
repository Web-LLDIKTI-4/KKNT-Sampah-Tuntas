<?php

namespace Tests\Concerns;

use App\Support\XlsxStreamer;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// Reads XlsxStreamer output (row 1 title, 2 info, 3 header, data from HEADER_ROW + 1)
trait ReadsXlsx
{
    protected function xlsxFromResponse(TestResponse $response, ?string $filenamePattern = null): Worksheet
    {
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
        if ($filenamePattern) {
            $this->assertMatchesRegularExpression($filenamePattern, (string) $response->headers->get('content-disposition'));
        }

        return $this->xlsxFromWriter(fn (string $path) => file_put_contents($path, $response->streamedContent()));
    }

    // $write receives a temp path to write the xlsx into
    protected function xlsxFromWriter(callable $write): Worksheet
    {
        $path = tempnam(sys_get_temp_dir(), 'qa_xlsx').'.xlsx';
        try {
            $write($path);

            return IOFactory::load($path)->getActiveSheet();
        } finally {
            @unlink($path);
        }
    }

    protected function cellText(Worksheet $ws, string $address): string
    {
        return (string) $ws->getCell($address)->getValue();
    }

    // Anti formula injection: value stored as text, never as formula
    protected function assertCellIsText(Worksheet $ws, string $address, string $expected): void
    {
        $this->assertContains($ws->getCell($address)->getDataType(), [DataType::TYPE_STRING, DataType::TYPE_INLINE], $address);
        $this->assertSame($expected, $this->cellText($ws, $address), $address);
    }

    protected function headerRow(Worksheet $ws): array
    {
        $row = XlsxStreamer::HEADER_ROW;

        return $ws->rangeToArray('A'.$row.':'.$ws->getHighestColumn().$row)[0];
    }

    // Data rows only (title, info and header skipped)
    protected function dataRows(Worksheet $ws): array
    {
        $first = XlsxStreamer::HEADER_ROW + 1;
        if ($ws->getHighestRow() < $first) {
            return [];
        }

        return $ws->rangeToArray('A'.$first.':'.$ws->getHighestColumn().$ws->getHighestRow());
    }

    protected function dataColumn(Worksheet $ws, int $index): array
    {
        return array_map(fn ($row) => $row[$index], $this->dataRows($ws));
    }
}
