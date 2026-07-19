<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Config\AppConfig;
use Nene2\Routing\Router;
use Nene2\View\HtmlResponseFactory;
use NeNeRecords\BundleField\BundleDocumentValidator;
use NeNeRecords\Http\BasePath;
use NeNeRecords\Http\EmbedAllowlist;
use NeNeRecords\Http\PublicHtmlCsp;
use NeNeRecords\Http\WebAnalyticsConfig;
use NeNeRecords\Http\WebAnalyticsHeadSnippet;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use NeNeRecords\Widget\ListWidgetsUseCaseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RenderPublicRecordViewHandler implements PublicRecordViewRendererInterface
{
    public function __construct(
        private GetPublicRecordViewUseCaseInterface $useCase,
        private ListPublicSettingsUseCaseInterface $publicSettings,
        private HtmlResponseFactory $html,
        private AppConfig $config,
        private string $projectRoot,
        private ResponseFactoryInterface $responseFactory,
        private PublicHtmlSanitizer $htmlSanitizer,
        private FrontPageSetting $frontPage,
        private ListWidgetsUseCaseInterface $listWidgets,
        /** Sub-directory install prefix (`APP_BASE_PATH`); '' = served at root. */
        private string $basePath = '',
    ) {
    }

    /**
     * The base every generated URL is prefixed with: the fixed install prefix
     * plus the per-request tenant prefix in directory mode (`/org1`), set by
     * OrgResolverMiddleware on `nene2.base_prefix`. '' = root single-tenant.
     */
    private function effectiveBase(ServerRequestInterface $request): string
    {
        return $this->basePath . (string) $request->getAttribute('nene2.base_prefix', '');
    }

    /**
     * Legacy `/view/{type}/{entitySlug}` twin → 301 to the canonical permalink.
     * The crawlable SSR now lives at the real permalink; this keeps one canonical
     * URL and avoids a duplicate-content twin.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $typeSlug = trim((string) ($parameters['slug'] ?? ''));
        $entitySlug = trim((string) ($parameters['entitySlug'] ?? ''));

        if ($typeSlug === '' || $entitySlug === '') {
            throw new PublicRecordNotFoundException(
                $typeSlug !== '' ? $typeSlug : 'unknown',
                $entitySlug !== '' ? $entitySlug : 'unknown',
            );
        }

        // Resolve to the canonical permalink (also 404s via the use case if missing).
        $output = $this->useCase->execute(new GetPublicRecordViewInput($typeSlug, $entitySlug));
        $uri = $request->getUri();
        $location = $uri->getScheme() . '://' . $uri->getAuthority()
            . BasePath::prefix($this->effectiveBase($request), $output->canonicalPath);

        return $this->responseFactory->createResponse(301)->withHeader('Location', $location);
    }

    /**
     * Render the crawlable record HTML for a resolved entity (by slug or id).
     * Shared by the `/view/` route and the real-permalink route.
     */
    public function renderEntity(
        string $typeSlug,
        ?string $entitySlug,
        ?int $entityId,
        ServerRequestInterface $request,
        bool $asFrontPage = false,
    ): ResponseInterface {
        $langParam = $request->getQueryParams()['lang'] ?? null;
        $locale = PublicLocale::resolve(is_string($langParam) ? $langParam : null);

        $output = $this->useCase->execute(
            new GetPublicRecordViewInput($typeSlug, $entitySlug, $entityId, $locale),
        );

        // If this record is the org's front page, its canonical home is `/`: send the
        // permalink there so there is a single home URL (#701). Skipped when we ARE
        // rendering the front page (the home edge layer calls with $asFrontPage = true).
        // A 302 — not 301 — because the pin is a mutable setting: after unpinning, the
        // permalink must become reachable again without fighting browser/CDN caches of a
        // permanent redirect. The query string is carried over so `?lang=` intent survives.
        if (!$asFrontPage) {
            $front = $this->frontPage->resolvePublished();

            if ($front !== null && $front[0]->id === $output->entityId) {
                $uri = $request->getUri();
                $home = $uri->getScheme() . '://' . $uri->getAuthority()
                    . BasePath::prefix($this->effectiveBase($request), '/');
                $query = $uri->getQuery();

                if ($query !== '') {
                    $home .= '?' . $query;
                }

                return $this->responseFactory->createResponse(302)->withHeader('Location', $home);
            }
        }

        $settings = $this->publicSettingsMap();
        $siteName = $settings['site_name'] ?? 'NeNe Records';
        $defaultMetaDescription = $settings['default_meta_description'] ?? '';

        // GA4 / GTM + Consent Mode v2 — emitted only when the org configured a tag id.
        $analytics = WebAnalyticsConfig::fromSettings($settings);
        $analyticsNonce = $analytics->isEnabled() ? bin2hex(random_bytes(16)) : '';
        $analyticsHead = WebAnalyticsHeadSnippet::render($analytics, $analyticsNonce);

        // Trusted-embed primitive (#802 Phase 2): when the org has an embed
        // allowlist, render its `trusted-embed` widgets into the crawlable shell
        // as validated <script> tags (see TrustedEmbedScripts). With no allowlist
        // configured we skip the widget query entirely, so a page with no embed
        // configured does exactly what it did before — no extra work, no output.
        $embedAllowlist = EmbedAllowlist::fromSettings($settings);
        $embedScripts = '';
        if (!$embedAllowlist->isEmpty()) {
            $embedScripts = TrustedEmbedScripts::render(
                $this->listWidgets->execute()->items,
                $embedAllowlist,
            );
        }

        // Canonical / og:url point at the user-facing permalink (not this /view/ twin).
        // For a negotiated locale the canonical self-references with `?lang=`, and
        // hreflang alternates advertise every locale variant (#540).
        $uri = $request->getUri();
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority();
        $effectiveBase = $this->effectiveBase($request);
        // As the front page (#701) the record is served at the site root, so canonical /
        // og:url point at `/` (not the record's own permalink) to avoid a duplicate-content
        // twin; the original permalink 301s here instead.
        $canonicalPath = $asFrontPage ? '/' : $output->canonicalPath;
        $permalinkUrl = $baseUrl . BasePath::prefix($effectiveBase, $canonicalPath);
        $canonicalUrl = $locale !== null ? $permalinkUrl . '?lang=' . $locale : $permalinkUrl;
        $htmlLang = $locale ?? PublicLocale::DEFAULT_LANG;
        $alternateLinks = [['hreflang' => 'x-default', 'href' => $permalinkUrl]];
        foreach (PublicLocale::SUPPORTED as $supported) {
            $alternateLinks[] = ['hreflang' => $supported, 'href' => $permalinkUrl . '?lang=' . $supported];
        }

        // og:image / twitter:image — absolutize the entity's social-card derivative.
        $ogImageUrl = null;
        if ($output->ogImagePath !== null) {
            $ogImageUrl = str_starts_with($output->ogImagePath, 'http')
                ? $output->ogImagePath
                : $baseUrl . BasePath::prefix($effectiveBase, $output->ogImagePath);
        }

        // Organization JSON-LD (#978): the company signal Google's knowledge panel reads.
        // logo_media_id is resolved to a (base-relative) media URL by ListPublicSettings;
        // absolutize it like the og:image so structured data carries an absolute URL.
        $logoValue = $settings['logo_media_id'] ?? '';
        $logoUrl = $logoValue === ''
            ? null
            : (str_starts_with($logoValue, 'http') ? $logoValue : $baseUrl . BasePath::prefix($effectiveBase, $logoValue));
        $homeUrl = $baseUrl . BasePath::prefix($effectiveBase, '/');
        $organizationLd = PublicOrganizationSchema::build($siteName, $homeUrl, $logoUrl, $settings);

        // Prefer the per-entity meta description, falling back to the site default.
        $metaDescription = $output->metaDescription !== ''
            ? $output->metaDescription
            : $defaultMetaDescription;

        $bootstrapJson = json_encode(
            $output->bootstrap,
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE,
        );

        $viteUrl = getenv('NENE_RECORDS_VITE_URL');

        if (!is_string($viteUrl) || $viteUrl === '') {
            $viteUrl = 'http://localhost:5173';
        }

        // Single-origin SPA mount: in dev, load the Vite dev client; in prod,
        // resolve the built entry assets so the SSR shell hydrates into the SPA.
        // Falls back to crawlable-SSR-only when no build is present.
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

        return $this->html->create('public/record-detail.php', [
            'pageTitle' => $output->pageTitle,
            'entityTypeSlug' => $output->entityTypeSlug,
            'entityTypeName' => $output->entityTypeName,
            'entityId' => $output->entityId,
            'displayFields' => $output->displayFields,
            'chapterNav' => $output->chapterNav,
            // The front page is a site root, not a node in the path hierarchy: drop the
            // breadcrumb trail + its BreadcrumbList JSON-LD (the template hides both when empty).
            // `bare` ships a fully custom page (its own chrome, its own CSS) and
            // `custom` hosts a bundle: neither wants the article scaffold the SPA
            // also omits. Passing the resolved layout lets the template match (#879).
            'layout' => $output->layout,
            'breadcrumbs' => $asFrontPage ? [] : $output->breadcrumbs,
            'childPages' => $output->childPages,
            // og:type is `website` for the home page, `article` for a normal record.
            'ogType' => $asFrontPage ? 'website' : 'article',
            'siteOrigin' => $baseUrl,
            'siteName' => $siteName,
            'metaDescription' => $metaDescription,
            'analyticsHead' => $analyticsHead,
            // Validated trusted-embed <script> tags for the crawlable shell (#802);
            // '' when the org has no allowlist / no trusted-embed widgets.
            'embedScripts' => $embedScripts,
            'htmlLang' => $htmlLang,
            'alternateLinks' => $alternateLinks,
            'basePath' => $effectiveBase,
            'canonicalUrl' => $canonicalUrl,
            'ogImageUrl' => $ogImageUrl,
            'organizationLd' => $organizationLd,
            'publishedAtIso' => $output->publishedAtIso,
            'updatedAtIso' => $output->updatedAtIso,
            'bootstrapJson' => $bootstrapJson,
            'spaMode' => $spaMode,
            'viteUrl' => rtrim($viteUrl, '/'),
            'spaJs' => $spaJs,
            'spaCss' => $spaCss,
            'spaPreload' => $spaPreload,
            'renderMarkdown' => static fn (string $markdown): string => PublicMarkdownRenderer::toSafeHtml($markdown),
            // html-typed fields (e.g. WXR-imported content): sanitize server-side so the
            // crawlable SSR shows the same trusted markup the SPA renders via DOMPurify.
            'renderHtml' => fn (string $rawHtml): string => $this->htmlSanitizer->sanitize($rawHtml),
            // A bundle's crawlable twin (#311): render its seoText markdown server-side
            // (the sandboxed iframe itself is SPA-only / invisible to crawlers).
            'renderBundleSeo' => static fn (string $raw): string => PublicMarkdownRenderer::toSafeHtml(BundleDocumentValidator::seoTextOf($raw)),
        ])->withHeader(
            'Content-Security-Policy',
            PublicHtmlCsp::build(
                $analytics,
                $analyticsNonce !== '' ? $analyticsNonce : null,
                $embedAllowlist,
            ),
        );
    }

    /**
     * All public settings flattened to a `settingKey => effectiveValue` map
     * (site chrome + analytics ids all read from one query).
     *
     * @return array<string, string>
     */
    private function publicSettingsMap(): array
    {
        $map = [];

        foreach ($this->publicSettings->execute()->items as $entry) {
            $map[$entry->def->settingKey] = $entry->effectiveValue;
        }

        return $map;
    }
}
