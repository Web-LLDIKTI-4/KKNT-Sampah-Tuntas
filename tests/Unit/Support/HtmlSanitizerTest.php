<?php

namespace Tests\Unit\Support;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_safe_link_is_kept_with_forced_attributes(): void
    {
        $out = HtmlSanitizer::clean('<p>Lihat <a href="https://contoh.id/a?b=1&c=2" onclick="x()" style="color:red" target="_self" rel="opener">di sini</a></p>');

        $this->assertSame('<p>Lihat <a href="https://contoh.id/a?b=1&amp;c=2" target="_blank" rel="noopener noreferrer">di sini</a></p>', $out);
    }

    public function test_uppercase_http_scheme_is_allowed(): void
    {
        $this->assertStringContainsString('href="HTTP://contoh.id"', HtmlSanitizer::clean('<a href="HTTP://contoh.id">x</a>'));
    }

    public static function xssPayloads(): array
    {
        return [
            'javascript' => ['<a href="javascript:alert(1)">klik</a>'],
            'javascript case' => ['<a href="JaVaScRiPt:alert(1)">klik</a>'],
            'javascript entity' => ['<a href="&#106;avascript:alert(1)">klik</a>'],
            'javascript hex entity' => ['<a href="&#x6A;&#x61;vascript:alert(1)">klik</a>'],
            'javascript tab' => ["<a href=\"java\tscript:alert(1)\">klik</a>"],
            'javascript newline entity' => ['<a href="java&#10;script:alert(1)">klik</a>'],
            'leading space' => ['<a href="  javascript:alert(1)">klik</a>'],
            'data uri' => ['<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">klik</a>'],
            'vbscript' => ['<a href="vbscript:msgbox(1)">klik</a>'],
            'protocol relative' => ['<a href="//evil.test">klik</a>'],
            'relative path' => ['<a href="/admin/hapus">klik</a>'],
            'http without host' => ['<a href="http:alert(1)">klik</a>'],
            'https backslash' => ['<a href="https:\\\\evil.test">klik</a>'],
            'no href' => ['<a name="x">klik</a>'],
        ];
    }

    #[DataProvider('xssPayloads')]
    public function test_unsafe_link_is_unwrapped_keeping_text(string $payload): void
    {
        $this->assertSame('klik', HtmlSanitizer::clean($payload));
    }

    public function test_event_handlers_and_scripts_are_removed(): void
    {
        $out = HtmlSanitizer::clean('<img src=x onerror=alert(1)><svg onload=alert(1)></svg><script>alert(1)</script><p onmouseover="alert(1)">ok</p>');

        $this->assertSame('<p>ok</p>', $out);
    }

    public function test_attribute_breakout_in_href_is_escaped(): void
    {
        $out = HtmlSanitizer::clean('<a href="https://contoh.id/&quot; onmouseover=&quot;alert(1)">x</a>');

        $this->assertStringNotContainsString('" onmouseover', $out);
        $this->assertStringContainsString('rel="noopener noreferrer"', $out);
    }
}
