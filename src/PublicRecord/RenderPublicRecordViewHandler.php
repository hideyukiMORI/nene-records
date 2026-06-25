<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Config\AppConfig;
use Nene2\Routing\Router;
use Nene2\View\HtmlResponseFactory;
use NeNeRecords\BundleField\BundleDocumentValidator;
use NeNeRecords\Http\PublicHtmlCsp;
use NeNeRecords\Http\WebAnalyticsConfig;
use NeNeRecords\Http\WebAnalyticsHeadSnippet;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RenderPublicRecordViewHandler
{
    public function __construct(
        private GetPublicRecordViewUseCaseInterface $useCase,
        private ListPublicSettingsUseCaseInterface $publicSettings,
        private HtmlResponseFactory $html,
        private AppConfig $config,
        private string $projectRoot,
        private ResponseFactoryInterface $responseFactory,
        private PublicHtmlSanitizer $htmlSanitizer,
    ) {
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
        $location = $uri->getScheme() . '://' . $uri->getAuthority() . $output->canonicalPath;

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
    ): ResponseInterface {
        $output = $this->useCase->execute(new GetPublicRecordViewInput($typeSlug, $entitySlug, $entityId));
        $settings = $this->publicSettingsMap();
        $siteName = $settings['site_name'] ?? 'NeNe Records';
        $defaultMetaDescription = $settings['default_meta_description'] ?? '';

        // GA4 / GTM + Consent Mode v2 — emitted only when the org configured a tag id.
        $analytics = WebAnalyticsConfig::fromSettings($settings);
        $analyticsNonce = $analytics->isEnabled() ? bin2hex(random_bytes(16)) : '';
        $analyticsHead = WebAnalyticsHeadSnippet::render($analytics, $analyticsNonce);

        // Canonical / og:url point at the user-facing permalink (not this /view/ twin).
        $uri = $request->getUri();
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority();
        $canonicalUrl = $baseUrl . $output->canonicalPath;

        // og:image / twitter:image — absolutize the entity's social-card derivative.
        $ogImageUrl = null;
        if ($output->ogImagePath !== null) {
            $ogImageUrl = str_starts_with($output->ogImagePath, 'http')
                ? $output->ogImagePath
                : $baseUrl . $output->ogImagePath;
        }

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
            'siteName' => $siteName,
            'metaDescription' => $metaDescription,
            'analyticsHead' => $analyticsHead,
            'canonicalUrl' => $canonicalUrl,
            'ogImageUrl' => $ogImageUrl,
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
            PublicHtmlCsp::build($analytics, $analyticsNonce !== '' ? $analyticsNonce : null),
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
