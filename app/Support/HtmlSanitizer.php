<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitasi HTML dari editor (Summernote) dengan allowlist tag.
 * Semua atribut dibuang sehingga event handler/URL berbahaya tidak lolos.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'sup', 'sub',
        'ul', 'ol', 'li', 'span', 'div', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    ];

    // Tag yang dibuang beserta seluruh isinya
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript', 'svg', 'math'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return e(strip_tags($html));
        }

        static::cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        return trim($output);
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $node->removeChild($child);

                    continue;
                }

                static::cleanChildren($child);

                $href = $tag === 'a' ? static::safeHref($child->getAttribute('href')) : null;

                if (! in_array($tag, self::ALLOWED_TAGS, true) && $href === null) {
                    // Pertahankan teks di dalam tag yang tidak diizinkan
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);

                    continue;
                }

                foreach (iterator_to_array($child->attributes) as $attr) {
                    $child->removeAttribute($attr->nodeName);
                }

                if ($href !== null) {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    /**
     * Tag <a> aman (hanya http/https), atau string kosong bila URL tidak valid.
     */
    public static function link(?string $url, ?string $label = null): string
    {
        $safe = static::safeUrl($url);

        return $safe ? '<a href="'.e($safe).'" target="_blank" rel="noopener noreferrer">'.e($label ?? $safe).'</a>' : '';
    }

    // href <a> di konten: DOM sudah decode entity; tolak karakter kontrol & wajib http(s) dengan host
    private static function safeHref(string $href): ?string
    {
        $href = trim($href);
        if ($href === '' || preg_match('/[\x00-\x1F\x7F]/', $href) || ! preg_match('~^https?://~i', $href)) {
            return null;
        }

        return parse_url($href, PHP_URL_HOST) ? static::safeUrl($href) : null;
    }

    /**
     * Link aman untuk ditampilkan: hanya http/https, selain itu dikembalikan null.
     */
    public static function safeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $scheme = strtolower((string) parse_url(trim($url), PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? trim($url) : null;
    }
}
