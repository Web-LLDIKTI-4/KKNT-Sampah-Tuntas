<?php

namespace App\Support;

use Illuminate\View\ComponentAttributeBag;

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

    /**
     * Tombol pembuka #modalku; setara <x-button :modal="$url">.
     */
    public static function modal(string $url, string $label, ?string $title = null): string
    {
        return static::button($label, ['modal' => $url], ['title' => $title]);
    }

    /**
     * Tombol Google Maps untuk koordinat absensi; '-' bila koordinat kosong.
     */
    public static function map(mixed $lat, mixed $lng): string
    {
        if ($lat === null || $lng === null) {
            return '-';
        }

        return static::button('Lihat Map', [
            'href' => 'https://www.google.com/maps?q='.(float) $lat.','.(float) $lng,
        ], ['target' => '_blank', 'rel' => 'noopener noreferrer']);
    }

    private static function button(string $label, array $props, array $attributes = []): string
    {
        return view('components.button.index', $props + [
            'slot' => $label,
            'attributes' => new ComponentAttributeBag(array_map('e', array_filter($attributes))),
        ])->render();
    }
}
