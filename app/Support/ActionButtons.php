<?php

namespace App\Support;

class ActionButtons
{
    /**
     * Kolom aksi DataTables; parameter sama dengan komponen <x-action-data>.
     */
    public static function make(
        ?string $urlView = null,
        ?string $urlEdit = null,
        ?string $urlDelete = null,
        string $idField = 'id',
        string|int|null $idValue = null,
    ): string {
        return view('components.action-data', compact('urlView', 'urlEdit', 'urlDelete', 'idField', 'idValue'))->render();
    }
}
