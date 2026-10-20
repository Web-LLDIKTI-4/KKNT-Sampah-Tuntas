<?php

namespace App\Support;

// Group title/subtotal row for XlsxStreamer; values keyed by column value key
final class XlsxSummaryRow
{
    public function __construct(public readonly string $label, public readonly array $values = []) {}
}
