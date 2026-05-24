<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListPublicSettingsHandler
{
    public function __construct(
        private ListPublicSettingsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->response->create([
            'items' => array_map(
                static fn (SettingEntry $entry) => SettingHttpMapper::entryToPublicArray($entry),
                $output->items,
            ),
        ]);
    }
}
