<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * Content-Security-Policy for public SPA-bearing HTML responses (server-rendered
 * record pages and the SPA shell fallback).
 *
 * The framework default `default-src 'self'` is too strict for the single-origin
 * SPA: it injects inline `<style>` (runtime theme tokens) and loads `data:` fonts,
 * which `default-src 'self'` blocks. This relaxes only `style-src` / `font-src` /
 * `img-src` — scripts stay `'self'` (no `script-src 'unsafe-inline'`), and API
 * responses keep the strict framework default.
 */
final class PublicHtmlCsp
{
    public const POLICY = "default-src 'self'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "font-src 'self' data:; "
        . "img-src 'self' data:";

    private function __construct()
    {
    }
}
