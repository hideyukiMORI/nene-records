<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Config\AppConfig;
use Nene2\Routing\Router;
use Nene2\View\HtmlResponseFactory;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RenderPublicRecordViewHandler
{
    public function __construct(
        private GetPublicRecordViewUseCaseInterface $useCase,
        private ListPublicSettingsUseCaseInterface $publicSettings,
        private HtmlResponseFactory $html,
        private AppConfig $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $slug = trim((string) ($parameters['slug'] ?? ''));
        $entityId = (int) ($parameters['entityId'] ?? 0);

        if ($slug === '' || $entityId <= 0) {
            throw new PublicRecordNotFoundException($slug !== '' ? $slug : 'unknown', max(0, $entityId));
        }

        $output = $this->useCase->execute(new GetPublicRecordViewInput($slug, $entityId));
        $siteSettings = $this->resolveSiteSettings();

        $bootstrapJson = json_encode(
            $output->bootstrap,
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE,
        );

        $viteUrl = getenv('NENE_RECORDS_VITE_URL');

        if (!is_string($viteUrl) || $viteUrl === '') {
            $viteUrl = 'http://localhost:5173';
        }

        return $this->html->create('public/record-detail.php', [
            'pageTitle' => $output->pageTitle,
            'entityTypeSlug' => $output->entityTypeSlug,
            'entityTypeName' => $output->entityTypeName,
            'entityId' => $output->entityId,
            'displayFields' => $output->displayFields,
            'siteName' => $siteSettings['site_name'],
            'metaDescription' => $siteSettings['default_meta_description'],
            'bootstrapJson' => $bootstrapJson,
            'includeViteClient' => $this->config->debug,
            'viteUrl' => rtrim($viteUrl, '/'),
        ]);
    }

    /** @return array{site_name: string, default_meta_description: string} */
    private function resolveSiteSettings(): array
    {
        $settings = [
            'site_name' => 'NeNe Records',
            'default_meta_description' => '',
        ];

        foreach ($this->publicSettings->execute()->items as $entry) {
            if (array_key_exists($entry->def->settingKey, $settings)) {
                $settings[$entry->def->settingKey] = $entry->effectiveValue;
            }
        }

        return $settings;
    }
}
