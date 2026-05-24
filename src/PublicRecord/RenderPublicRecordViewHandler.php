<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Config\AppConfig;
use Nene2\Routing\Router;
use Nene2\View\HtmlResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RenderPublicRecordViewHandler
{
    public function __construct(
        private GetPublicRecordViewUseCaseInterface $useCase,
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
            'bootstrapJson' => $bootstrapJson,
            'includeViteClient' => $this->config->debug,
            'viteUrl' => rtrim($viteUrl, '/'),
        ]);
    }
}
