<?php

declare(strict_types=1);

namespace NeNeRecords\BundleField;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

/**
 * Server-side validator for a `bundle` field value (#311 / #491 WS3-S3a).
 *
 * A bundle is the "fully custom page" escape hatch: a self-contained HTML/JS/CSS
 * document rendered ONLY inside a sandboxed iframe on the public site. Because
 * iframe content is invisible to crawlers and assistive tech, the dual-
 * representation contract requires a crawlable text twin. So the stored value is
 * a small JSON envelope:
 *
 *   { "html": "<…sandboxed document…>", "seoText": "# markdown crawlable text" }
 *
 * `seoText` is REQUIRED (no cloaking; it doubles as the noscript / a11y
 * fallback). This is the trust boundary; the admin editor is convenience only.
 */
final class BundleDocumentValidator
{
    // text_fields.value is LONGTEXT (#491 WS3-S3b), so a real self-contained bundle fits.
    public const MAX_HTML_LEN = 1000000;
    public const MAX_SEO_TEXT_LEN = 50000;

    public function validate(string $json): void
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new ValidationException([
                new ValidationError('value', 'Bundle must be a JSON object { html, seoText }.', 'invalid'),
            ]);
        }

        $errors = [];

        $html = $decoded['html'] ?? null;
        if (!is_string($html) || strlen($html) > self::MAX_HTML_LEN) {
            $errors[] = new ValidationError('value.html', 'Bundle html must be a string (max ' . self::MAX_HTML_LEN . ' chars).', 'invalid');
        }

        $seoText = $decoded['seoText'] ?? null;
        if (!is_string($seoText) || trim($seoText) === '' || strlen($seoText) > self::MAX_SEO_TEXT_LEN) {
            $errors[] = new ValidationError('value.seoText', 'Bundle requires non-empty crawlable seoText (max ' . self::MAX_SEO_TEXT_LEN . ' chars).', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** Extract the crawlable seoText (markdown) from a stored bundle value; '' if malformed. */
    public static function seoTextOf(string $json): string
    {
        $decoded = json_decode($json, true);
        if (is_array($decoded) && isset($decoded['seoText']) && is_string($decoded['seoText'])) {
            return $decoded['seoText'];
        }

        return '';
    }
}
