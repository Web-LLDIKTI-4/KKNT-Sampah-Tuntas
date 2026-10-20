<?php

namespace App\Support;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

// Standard streamed XLSX layout for every export: title, info, header, then data rows (constant memory)
class XlsxStreamer
{
    // Row 1 title, row 2 info, row 3 header, data starts at row 4
    public const HEADER_ROW = 3;

    private const HEADER_FILL = 'D9EAD3';

    private const BORDER_COLOR = 'A6A6A6';

    private array $info = [];

    private ?array $total = null;

    private bool $numbered = true;

    private ?Style $percentStyle = null;

    /**
     * @param  array<int, array{label: string, value: Closure|string, width?: float, format?: ?string}>  $columns
     */
    public function __construct(private string $title, private array $columns) {}

    // format: null (auto), 'date' (d-m-Y), 'datetime' (d-m-Y H:i), 'number', 'percent' (0–100 → 0.00%)
    public static function column(string $label, Closure|string $value, float $width = 20, ?string $format = null): array
    {
        return ['label' => $label, 'value' => $value, 'width' => $width, 'format' => $format];
    }

    // Active filters/keyword shown under the title; empty values are skipped
    public function info(array $info): static
    {
        $this->info = array_filter($info, fn ($value) => $value !== null && $value !== '');

        return $this;
    }

    // false = no "No." column (default true)
    public function numbered(bool $numbered): static
    {
        $this->numbered = $numbered;

        return $this;
    }

    // Bold row yielded among data rows (group title/subtotal); same layout as total()
    public static function summary(string $label, array $values = []): XlsxSummaryRow
    {
        return new XlsxSummaryRow($label, $values);
    }

    // Bold summary row after the data; $values keyed by column value key, label spans the leading columns
    public function total(array $values, string $label = 'Total'): static
    {
        $this->total = ['label' => $label, 'values' => $values];

        return $this;
    }

    public function download(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(fn () => $this->writeTo('php://output', $rows), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function writeTo(string $path, iterable $rows): void
    {
        $offset = $this->numbered ? 1 : 0;
        $count = count($this->columns) + $offset;
        // Per-export temp folder, removed even if the export fails
        $dir = storage_path('app/tmp/xlsx-'.Str::uuid());
        File::ensureDirectoryExists($dir, 0700);

        try {
            $options = new Options;
            $options->setTempFolder($dir);
            if ($this->numbered) {
                $options->setColumnWidth(6, 1);
            }
            foreach (array_values($this->columns) as $i => $column) {
                $options->setColumnWidth($column['width'] ?? 20, $i + 1 + $offset);
            }
            $options->mergeCells(0, 1, $count - 1, 1);
            $options->mergeCells(0, 2, $count - 1, 2);

            $writer = new Writer($options);
            $writer->openToFile($path);
            $writer->getCurrentSheet()
                ->setName($this->sheetName())
                ->setSheetView((new SheetView)->setFreezeRow(self::HEADER_ROW + 1));

            $writer->addRow(new Row([new StringCell($this->title, null)], (new Style)->setFontBold()->setFontSize(14)));
            $writer->addRow(new Row([new StringCell($this->infoLine(), null)], (new Style)->setFontItalic()->setFontSize(10)->setFontColor('595959')));
            $writer->addRow(new Row($this->headerCells(), $this->headerStyle()));

            $rowStyle = $this->bodyStyle();
            $numberStyle = (new Style)->setCellAlignment(CellAlignment::CENTER);
            $number = 0;
            $written = 0;
            foreach ($rows as $row) {
                $written++;
                if ($row instanceof XlsxSummaryRow) {
                    $writer->addRow($this->totalRow($options, self::HEADER_ROW + $written, $row->label, $row->values));

                    continue;
                }
                $cells = $this->numbered ? [new NumericCell(++$number, $numberStyle)] : [];
                foreach ($this->columns as $column) {
                    $value = $column['value'] instanceof Closure ? ($column['value'])($row) : data_get($row, $column['value']);
                    $cells[] = $this->cell($value, $column['format'] ?? null);
                }
                $writer->addRow(new Row($cells, $rowStyle));
            }

            if ($this->total !== null) {
                $writer->addRow($this->totalRow($options, self::HEADER_ROW + $written + 1, $this->total['label'], $this->total['values']));
            }

            $writer->close();
        } finally {
            File::deleteDirectory($dir);
        }
    }

    private function headerCells(): array
    {
        $labels = array_merge($this->numbered ? ['No.'] : [], array_column($this->columns, 'label'));

        return array_map(fn ($label) => new StringCell((string) $label, null), $labels);
    }

    private function totalRow(Options $options, int $rowIndex, string $label, array $values): Row
    {
        $cells = $this->numbered ? [new StringCell($label, null)] : [];
        $span = 0;
        foreach (array_values($this->columns) as $i => $column) {
            $key = is_string($column['value']) ? $column['value'] : null;
            if ($key !== null && array_key_exists($key, $values)) {
                $cells[] = $this->cell($values[$key], $column['format'] ?? null);
            } else {
                // Without No. column the label takes the first column
                $cells[] = new StringCell(! $this->numbered && $i === 0 ? $label : '', null);
                if ($span === $i) {
                    $span++;
                }
            }
        }
        // Merge label over No. and leading non-total columns (merge rows are 1-based)
        $last = $this->numbered ? $span : $span - 1;
        if ($last > 0) {
            $options->mergeCells(0, $rowIndex, $last, $rowIndex);
        }

        return new Row($cells, $this->headerStyle());
    }

    private function infoLine(): string
    {
        $parts = ['Diekspor: '.now()->format('d-m-Y H:i')];
        foreach ($this->info as $label => $value) {
            $parts[] = "{$label}: {$value}";
        }

        return implode('  |  ', $parts);
    }

    // Text always goes into StringCell so user input is never written as a formula
    private function cell(mixed $value, ?string $format): Cell
    {
        if ($value === null || $value === '') {
            return new StringCell('-', null);
        }

        if ($value instanceof DateTimeInterface || $format === 'date' || $format === 'datetime') {
            return new StringCell($this->formatDate($value, $format === 'date' ? 'd-m-Y' : 'd-m-Y H:i'), null);
        }

        if ($format === 'percent' && is_numeric($value)) {
            return new NumericCell(($value + 0) / 100, $this->percentStyle ??= (new Style)->setFormat('0.00%'));
        }

        if (is_int($value) || is_float($value)) {
            return new NumericCell($value, null);
        }

        if ($format === 'number' && is_numeric($value)) {
            return new NumericCell($value + 0, null);
        }

        if (is_bool($value)) {
            return new StringCell($value ? 'Ya' : 'Tidak', null);
        }

        return new StringCell((string) $value, null);
    }

    private function formatDate(mixed $value, string $pattern): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($pattern);
        }

        if (str_starts_with((string) $value, '0000-00-00')) {
            return '-';
        }

        try {
            return Carbon::parse((string) $value)->format($pattern);
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function headerStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setBackgroundColor(self::HEADER_FILL)
            ->setBorder($this->border())
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    private function bodyStyle(): Style
    {
        return (new Style)
            ->setBorder($this->border())
            ->setShouldWrapText()
            ->setCellVerticalAlignment(CellVerticalAlignment::TOP);
    }

    private function border(): Border
    {
        return new Border(...array_map(
            fn ($side) => new BorderPart($side, self::BORDER_COLOR, Border::WIDTH_THIN, Border::STYLE_SOLID),
            [Border::TOP, Border::RIGHT, Border::BOTTOM, Border::LEFT]
        ));
    }

    // Excel sheet names: max 31 chars, no []:*?/\
    private function sheetName(): string
    {
        $name = trim(mb_substr(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $this->title), 0, 31), " '");

        return $name !== '' ? $name : 'Data';
    }
}
