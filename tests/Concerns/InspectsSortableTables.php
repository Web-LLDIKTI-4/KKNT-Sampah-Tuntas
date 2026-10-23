<?php

namespace Tests\Concerns;

use DOMDocument;
use DOMElement;
use DOMXPath;

// Helper untuk kontrak markup public/js/table-sort.js
trait InspectsSortableTables
{
    protected function xpathOf(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    protected function findTable(DOMXPath $xpath, string $query): DOMElement
    {
        $tables = $xpath->query($query);
        $this->assertSame(1, $tables->length, "Harus ada tepat satu tabel: {$query}");

        return $tables->item(0);
    }

    // $sortable = index th (0-based) yang wajib punya tombol sort => type
    protected function assertSortableHeader(DOMXPath $xpath, DOMElement $table, array $sortable): void
    {
        $this->assertTrue($table->hasAttribute('data-sortable'), 'Tabel harus punya data-sortable');

        $ths = $xpath->query('./thead/tr/th', $table);
        foreach ($ths as $i => $th) {
            $buttons = $xpath->query('./button[contains(@class,"table-sort")]', $th);
            if (! array_key_exists($i, $sortable)) {
                $this->assertSame(0, $buttons->length, "th #{$i} (".trim($th->textContent).') tidak boleh sortable');

                continue;
            }
            $this->assertSame(1, $buttons->length, "th #{$i} (".trim($th->textContent).') harus punya satu tombol sort');
            $this->assertSame('button', $buttons->item(0)->getAttribute('type'));
            $this->assertSame($sortable[$i], $buttons->item(0)->getAttribute('data-sort-type'), "th #{$i} data-sort-type");
        }

        $this->assertSame(count($sortable), $xpath->query('.//button[contains(@class,"table-sort")]', $table)->length);
    }

    protected function bodyRows(DOMXPath $xpath, DOMElement $table, string $predicate = ''): array
    {
        return iterator_to_array($xpath->query('./tbody/tr'.$predicate, $table));
    }
}
