<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Nene2\Http\ConditionalGetHelper;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetPublicRecordViewHandler
{
    private const CACHE_CONTROL = 'public, max-age=60, stale-while-revalidate=300';

    public function __construct(
        private GetPublicRecordViewUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

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

        $langParam = $request->getQueryParams()['lang'] ?? null;
        $locale = PublicLocale::resolve(is_string($langParam) ? $langParam : null);

        $output = $this->useCase->execute(new GetPublicRecordViewInput($typeSlug, $entitySlug, null, $locale));

        $etag = '"' . md5(json_encode($output->bootstrap, JSON_THROW_ON_ERROR)) . '"';

        $notModified = ConditionalGetHelper::check($request, $this->responseFactory, $etag);
        if ($notModified !== null) {
            return $notModified->withHeader('Cache-Control', self::CACHE_CONTROL);
        }

        return $this->response->create($output->bootstrap, 200, [
            'Cache-Control' => self::CACHE_CONTROL,
            'ETag' => $etag,
        ]);
    }
}
