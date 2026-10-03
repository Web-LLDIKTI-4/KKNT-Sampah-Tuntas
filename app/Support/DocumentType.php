<?php

namespace App\Support;

class DocumentType
{
    private const TYPES = [
        'pdf' => ['label' => 'PDF', 'icon' => 'ri-file-pdf-2-line', 'tone' => 'pdf'],
        'doc' => ['label' => 'Word', 'icon' => 'ri-file-word-line', 'tone' => 'word'],
        'docx' => ['label' => 'Word', 'icon' => 'ri-file-word-line', 'tone' => 'word'],
        'xls' => ['label' => 'Excel', 'icon' => 'ri-file-excel-line', 'tone' => 'excel'],
        'xlsx' => ['label' => 'Excel', 'icon' => 'ri-file-excel-line', 'tone' => 'excel'],
        'ppt' => ['label' => 'PowerPoint', 'icon' => 'ri-file-ppt-line', 'tone' => 'ppt'],
        'pptx' => ['label' => 'PowerPoint', 'icon' => 'ri-file-ppt-line', 'tone' => 'ppt'],
    ];

    private const FALLBACK = ['label' => 'Dokumen', 'icon' => 'ri-file-text-line', 'tone' => 'other'];

    /**
     * @return array{label: string, icon: string, tone: string}
     */
    public static function fromFileName(string $fileName): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return self::TYPES[$extension] ?? self::FALLBACK;
    }
}
