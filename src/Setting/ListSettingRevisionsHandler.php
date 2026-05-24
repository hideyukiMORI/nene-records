<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListSettingRevisionsHandler
{
    private const KEY_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public function __construct(
        private ListSettingRevisionsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $settingKey = trim((string) ($parameters['key'] ?? ''));

        if ($settingKey === '' || preg_match(self::KEY_PATTERN, $settingKey) !== 1) {
            throw new SettingKeyNotFoundException($settingKey !== '' ? $settingKey : 'unknown');
        }

        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListSettingRevisionsInput(
            settingKey: $settingKey,
            limit: $pagination->limit,
            offset: $pagination->offset,
        ));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (SettingRevision $revision) => SettingHttpMapper::revisionToArray($revision),
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}
