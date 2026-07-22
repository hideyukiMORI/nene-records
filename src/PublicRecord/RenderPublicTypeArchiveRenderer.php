<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Config\AppConfig;
use Nene2\View\HtmlResponseFactory;
use NeNeRecords\Http\BasePath;
use NeNeRecords\Http\PublicHtmlCsp;
use NeNeRecords\Http\WebAnalyticsConfig;
use NeNeRecords\Http\WebAnalyticsHeadSnippet;
use NeNeRecords\Media\MediaDerivativeUrl;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders a type archive as crawlable HTML that the SPA hydrates over (#877).
 *
 * Mirrors {@see RenderPublicRecordViewHandler}'s head/SPA-mount contract (base href,
 * analytics snippet, canonical, Vite entry, CSP) so both public surfaces behave the
 * same. It ships no bootstrap payload: unlike the record page, the SPA's
 * `PublicBrowsePage` fetches its own list from the public API, so the SSR markup only
 * has to be correct for crawlers and no-JS visitors until React mounts.
 */
final readonly class RenderPublicTypeArchiveRenderer implements PublicTypeArchiveRendererInterface
{
    public function __construct(
        private ListPublicSettingsUseCaseInterface $publicSettings,
        private HtmlResponseFactory $html,
        private AppConfig $config,
        private string $projectRoot,
        /** Sub-directory install prefix (`APP_BASE_PATH`); '' = served at root. */
        private string $basePath = '',
    ) {
    }

    public function render(GetPublicTypeArchiveOutput $archive, ServerRequestInterface $request): ResponseInterface
    {
        $settings = $this->publicSettingsMap();
        $siteName = $settings['site_name'] ?? 'NeNe Records';
        $metaDescription = $settings['default_meta_description'] ?? '';

        $analytics = WebAnalyticsConfig::fromSettings($settings);

        // First-party floating CTA (#982) for the type-archive shell — same server-rendered
        // chrome as the record shell. '' when disabled / the archive type is excluded. One
        // nonce covers analytics AND the dismiss script (#982 P2 a); generated only when
        // needed so pages with neither keep the strict `script-src 'self'`.
        $floatingCtaConfig = FloatingCta::fromSettings($settings);
        $fabPath = $request->getUri()->getPath();
        $fabNeedsScript = $floatingCtaConfig->needsScriptFor($archive->typeSlug, $fabPath);
        $nonce = ($analytics->isEnabled() || $fabNeedsScript) ? bin2hex(random_bytes(16)) : '';
        $analyticsHead = WebAnalyticsHeadSnippet::render($analytics, $nonce);

        $floatingCta = FloatingCtaHtml::render(
            $floatingCtaConfig,
            $archive->typeSlug,
            $fabPath,
            $fabNeedsScript ? $nonce : null,
            PublicLocale::DEFAULT_LANG,
        );

        $effectiveBase = $this->basePath . (string) $request->getAttribute('nene2.base_prefix', '');
        $uri = $request->getUri();
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority();
        $archiveUrl = $baseUrl . BasePath::prefix($effectiveBase, '/' . $archive->typeSlug);

        // Archives have no image field of their own, so the social card comes from
        // the site-wide `default_og_image` setting when set, else stays imageless (#912).
        // The setting is already a media URL; run it through the 'og' derivative.
        $ogImagePath = MediaDerivativeUrl::forPreset($settings['default_og_image'] ?? '', 'og');
        $ogImageUrl = $ogImagePath === null
            ? null
            : (str_starts_with($ogImagePath, 'http') ? $ogImagePath : $baseUrl . BasePath::prefix($effectiveBase, $ogImagePath));

        $prev = $archive->prevOffset();
        $next = $archive->nextOffset();

        $spaMode = 'none';
        $spaJs = null;
        $spaCss = [];
        $spaPreload = [];

        if ($this->config->debug) {
            $spaMode = 'dev';
        } else {
            $entry = ViteManifest::resolveEntry($this->projectRoot . '/frontend/dist/.vite/manifest.json');
            if ($entry !== null) {
                $spaMode = 'prod';
                $spaJs = $entry['js'];
                $spaCss = $entry['css'];
                $spaPreload = $entry['preload'];
            }
        }

        $from = $archive->total === 0 ? 0 : $archive->offset + 1;
        $to = min($archive->offset + count($archive->items), $archive->total);

        return $this->html->create('public/type-archive.php', [
            'pageTitle' => $archive->typeName,
            'typeName' => $archive->typeName,
            'items' => $archive->items,
            'htmlLang' => PublicLocale::DEFAULT_LANG,
            'basePath' => $effectiveBase,
            'siteName' => $siteName,
            'metaDescription' => $metaDescription,
            'ogImageUrl' => $ogImageUrl,
            'analyticsHead' => $analyticsHead,
            // Server-generated floating CTA chrome (#982); '' when disabled / no match.
            'floatingCta' => $floatingCta,
            // The first page is the archive's canonical address; deeper pages carry
            // their offset so each paged view has its own stable URL.
            'canonicalUrl' => $archive->offset === 0 ? $archiveUrl : $this->pageUrl($archiveUrl, $archive->offset),
            'prevUrl' => $prev === null ? null : $this->pageUrl($archiveUrl, $prev),
            'nextUrl' => $next === null ? null : $this->pageUrl($archiveUrl, $next),
            'prevLabel' => 'Previous',
            'nextLabel' => 'Next',
            'countLabel' => $archive->total === 0
                ? '0'
                : sprintf('%d–%d / %d', $from, $to, $archive->total),
            'emptyLabel' => 'No published records yet.',
            'spaMode' => $spaMode,
            'viteUrl' => rtrim($this->viteUrl(), '/'),
            'spaJs' => $spaJs,
            'spaCss' => $spaCss,
            'spaPreload' => $spaPreload,
        ])->withHeader(
            'Content-Security-Policy',
            PublicHtmlCsp::build($analytics, $nonce !== '' ? $nonce : null, null),
        );
    }

    private function pageUrl(string $archiveUrl, int $offset): string
    {
        return $offset === 0 ? $archiveUrl : $archiveUrl . '?offset=' . $offset;
    }

    private function viteUrl(): string
    {
        $viteUrl = getenv('NENE_RECORDS_VITE_URL');

        return is_string($viteUrl) && $viteUrl !== '' ? $viteUrl : 'http://localhost:5173';
    }

    /** @return array<string, string> */
    private function publicSettingsMap(): array
    {
        $map = [];

        foreach ($this->publicSettings->execute()->items as $entry) {
            $map[$entry->def->settingKey] = $entry->effectiveValue;
        }

        return $map;
    }
}
