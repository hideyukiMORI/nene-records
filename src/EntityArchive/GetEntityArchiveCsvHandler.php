<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetEntityArchiveCsvHandler
{
    private const int PAGE_SIZE = 500;

    public function __construct(
        private EntityArchiveRepositoryInterface $archive,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityTypeId = (int) ($parameters['entity_type_id'] ?? 0);

        $output = fopen('php://temp', 'r+');

        if ($output === false) {
            throw new \RuntimeException('Failed to open temp stream.');
        }

        fputcsv($output, [
            'original_entity_id',
            'entity_type_slug',
            'entity_type_name',
            'entity_slug',
            'entity_status',
            'deleted_at',
            'archived_at',
            'archived_reason',
            'snapshot',
        ], escape: '\\');

        $offset = 0;

        do {
            $entries = $this->archive->findByEntityTypeId($entityTypeId, self::PAGE_SIZE, $offset);

            foreach ($entries as $entry) {
                fputcsv($output, escape: '\\', fields: [
                    $entry->originalEntityId,
                    $entry->entityTypeSlug,
                    $entry->entityTypeName,
                    $entry->entitySlug ?? '',
                    $entry->entityStatus,
                    $entry->deletedAt?->format('Y-m-d H:i:s') ?? '',
                    $entry->archivedAt->format('Y-m-d H:i:s'),
                    $entry->archivedReason,
                    json_encode($entry->snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ]);
            }


            $offset += self::PAGE_SIZE;
        } while (count($entries) === self::PAGE_SIZE);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filename = "entity_archive_{$entityTypeId}_" . date('Ymd_His') . '.csv';

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->withBody($this->streamFromString($csv !== false ? $csv : ''));
    }

    private function streamFromString(string $content): \Psr\Http\Message\StreamInterface
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new \RuntimeException('Failed to create stream.');
        }

        fwrite($stream, $content);
        rewind($stream);

        return new \Nyholm\Psr7\Stream($stream);
    }
}
