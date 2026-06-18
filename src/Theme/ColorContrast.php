<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

/**
 * WCAG contrast for theme token values (#433). Parses the two colour formats the
 * token contract actually uses — `#hex` and `oklch(L C H)` — into a WCAG
 * relative luminance, then computes the contrast ratio. Values it cannot parse
 * (color-mix(), var(), gradients) return null so the caller can report
 * "not computed" rather than guess.
 */
final class ColorContrast
{
    /**
     * Contrast ratio (1–21) between two colours, or null if either is
     * uncomputable. ratio = (Llighter + 0.05) / (Ldarker + 0.05).
     */
    public static function ratio(string $a, string $b): ?float
    {
        $la = self::relativeLuminance($a);
        $lb = self::relativeLuminance($b);
        if ($la === null || $lb === null) {
            return null;
        }
        $hi = max($la, $lb);
        $lo = min($la, $lb);

        return ($hi + 0.05) / ($lo + 0.05);
    }

    /** WCAG relative luminance for a `#hex` or `oklch()` colour; null otherwise. */
    public static function relativeLuminance(string $color): ?float
    {
        $value = strtolower(trim($color));

        $rgb = self::parseHex($value) ?? self::parseOklch($value);
        if ($rgb === null) {
            return null;
        }

        [$r, $g, $b] = $rgb;

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Parse `#rgb` / `#rrggbb` (alpha ignored) into linear-light sRGB [0,1]^3.
     *
     * @return array{float, float, float}|null
     */
    private static function parseHex(string $value): ?array
    {
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})([0-9a-f]{2})?$/', $value, $m) !== 1) {
            return null;
        }
        $hex = $m[1];
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = (int) hexdec(substr($hex, 0, 2)) / 255;
        $g = (int) hexdec(substr($hex, 2, 2)) / 255;
        $b = (int) hexdec(substr($hex, 4, 2)) / 255;

        return [self::srgbToLinear($r), self::srgbToLinear($g), self::srgbToLinear($b)];
    }

    /**
     * Parse `oklch(L C H)` (L as 0-1 or %, optional /alpha) into linear-light
     * sRGB [0,1]^3, via oklab (Björn Ottosson's matrices).
     *
     * @return array{float, float, float}|null
     */
    private static function parseOklch(string $value): ?array
    {
        if (preg_match('/^oklch\(\s*([0-9.]+%?)\s+([0-9.]+)\s+([0-9.]+)(?:deg)?\s*(?:\/[^)]*)?\)$/', $value, $m) !== 1) {
            return null;
        }

        $lRaw = $m[1];
        if (str_ends_with($lRaw, '%')) {
            $l = (float) rtrim($lRaw, '%') / 100;
        } else {
            $lNum = (float) $lRaw;
            $l = $lNum > 1.0 ? $lNum / 100 : $lNum;
        }
        $c = (float) $m[2];
        $hDeg = (float) $m[3];
        $h = deg2rad($hDeg);

        $a = $c * cos($h);
        $b = $c * sin($h);

        $lp = $l + 0.3963377774 * $a + 0.2158037573 * $b;
        $mp = $l - 0.1055613458 * $a - 0.0638541728 * $b;
        $sp = $l - 0.0894841775 * $a - 1.2914855480 * $b;

        $l3 = $lp ** 3;
        $m3 = $mp ** 3;
        $s3 = $sp ** 3;

        $r = 4.0767416621 * $l3 - 3.3077115913 * $m3 + 0.2309699292 * $s3;
        $g = -1.2684380046 * $l3 + 2.6097574011 * $m3 - 0.3413193965 * $s3;
        $bl = -0.0041960863 * $l3 - 0.7034186147 * $m3 + 1.7076147010 * $s3;

        return [self::clamp01($r), self::clamp01($g), self::clamp01($bl)];
    }

    /** sRGB channel (0-1) → linear-light. */
    private static function srgbToLinear(float $c): float
    {
        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    private static function clamp01(float $c): float
    {
        return max(0.0, min(1.0, $c));
    }
}
