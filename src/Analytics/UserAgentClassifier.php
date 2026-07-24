<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

/**
 * Derives a coarse device class + bot flag from a User-Agent (ADR 0006 D5).
 *
 * The raw UA is never stored — only this classification is. Bot detection is a substring
 * match against common crawler / preview / tool markers (best effort, not exhaustive):
 * bots that do not run JS are also excluded from the LP beacon by nature, so the two
 * signals reinforce each other.
 */
final class UserAgentClassifier
{
    /** @var list<string> */
    private const BOT_MARKERS = [
        'bot', 'crawl', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit', 'embedly',
        'quora link preview', 'pinterest', 'vkshare', 'w3c_validator', 'curl', 'wget',
        'python-requests', 'httpclient', 'go-http-client', 'headless', 'lighthouse',
        'ahrefs', 'semrush', 'mj12', 'dotbot', 'petalbot', 'bytespider', 'gptbot', 'ccbot',
        'claudebot', 'google-inspectiontool', 'applebot', 'yandex', 'baiduspider',
    ];

    /**
     * @return array{type: string, isBot: bool} type is one of bot|mobile|desktop|other
     */
    public static function classify(string $userAgent): array
    {
        $ua = strtolower(trim($userAgent));

        if ($ua === '') {
            return ['type' => 'other', 'isBot' => false];
        }

        foreach (self::BOT_MARKERS as $marker) {
            if (str_contains($ua, $marker)) {
                return ['type' => 'bot', 'isBot' => true];
            }
        }

        if (
            preg_match('/(android|iphone|ipod|ipad|iemobile|blackberry|windows phone|mobile)/', $ua) === 1
        ) {
            return ['type' => 'mobile', 'isBot' => false];
        }

        if (preg_match('/(windows nt|macintosh|mac os x|x11|linux|cros)/', $ua) === 1) {
            return ['type' => 'desktop', 'isBot' => false];
        }

        return ['type' => 'other', 'isBot' => false];
    }
}
