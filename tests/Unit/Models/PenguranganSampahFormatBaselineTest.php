<?php

namespace Tests\Unit\Models;

use App\Models\PenguranganSampah;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PenguranganSampahFormatBaselineTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'persen' => [12.5, '%', '12,50%'],
            'persen nol' => [0.0, '%', '0,00%'],
            'persen 100' => [100.0, '%', '100,00%'],
            'satuan null fallback persen' => [12.5, null, '12,50%'],
            'kg ribuan' => [1250.0, 'kg', '1.250,00 kg'],
            'kg desimal' => [150.75, 'kg', '150,75 kg'],
            'kg maksimum' => [99999999.99, 'kg', '99.999.999,99 kg'],
            'nilai null persen' => [null, '%', '-'],
            'nilai null kg' => [null, 'kg', '-'],
            'nilai null satuan null' => [null, null, '-'],
        ];
    }

    #[DataProvider('cases')]
    public function test_format_baseline(?float $nilai, ?string $satuan, string $expected): void
    {
        $this->assertSame($expected, PenguranganSampah::formatBaseline($nilai, $satuan));
    }
}
