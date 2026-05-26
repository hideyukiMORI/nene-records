<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\QueryStringParser;
use NeNeRecords\TextField\TextFieldRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ExportEntitiesHandler
{
    private const int EXPORT_MAX = 10_000;

    public function __construct(
        private EntityRepositoryInterface $entities,
        private TextFieldRepositoryInterface $textFields,
        private ResponseFactoryInterface $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $entityTypeId = QueryStringParser::int($request, 'entity_type_id');
        $rawStatus = $request->getQueryParams()['status'] ?? null;
        $status = is_string($rawStatus) ? EntityStatus::tryFrom($rawStatus) : null;

        $rawQ = $request->getQueryParams()['q'] ?? null;
        $q = (is_string($rawQ) && $rawQ !== '') ? $rawQ : null;

        $rawFormat = $request->getQueryParams()['format'] ?? 'json';
        $format = ($rawFormat === 'csv') ? 'csv' : 'json';

        $criteria = new EntityListCriteria(
            entityTypeId: $entityTypeId,
            status: $status,
            q: $q,
        );

        $entityList = $this->entities->findByCriteria($criteria, self::EXPORT_MAX, 0);

        if ($entityList === []) {
            return $format === 'csv'
                ? $this->csvResponse([], [])
                : $this->jsonResponse([]);
        }

        $entityIds = array_map(static fn (Entity $e) => (int) $e->id, $entityList);
        $allTextFields = $this->textFields->findByEntityIds($entityIds);

        // Group text fields by entity id
        $fieldsByEntity = [];
        foreach ($allTextFields as $tf) {
            $fieldsByEntity[$tf->entityId][$tf->fieldKey] = $tf->value;
        }

        // Collect all unique field keys for column ordering
        $fieldKeys = [];
        foreach ($fieldsByEntity as $fields) {
            foreach (array_keys($fields) as $key) {
                $fieldKeys[$key] = true;
            }
        }
        $fieldKeys = array_keys($fieldKeys);
        sort($fieldKeys);

        if ($format === 'csv') {
            return $this->csvResponse($entityList, $fieldKeys, $fieldsByEntity);
        }

        return $this->jsonResponse($entityList, $fieldKeys, $fieldsByEntity);
    }

    /**
     * @param list<Entity> $entities
     * @param list<string> $fieldKeys
     * @param array<int, array<string, string>> $fieldsByEntity
     */
    private function csvResponse(array $entities, array $fieldKeys, array $fieldsByEntity = []): ResponseInterface
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Failed to open temp stream.');
        }

        $headers = ['id', 'slug', 'status', 'published_at', ...$fieldKeys];
        fputcsv($handle, $headers);

        foreach ($entities as $entity) {
            $row = [
                $entity->id,
                $entity->slug ?? '',
                $entity->status->value,
                $entity->publishedAt?->format(\DateTimeInterface::ATOM) ?? '',
            ];

            $fields = $fieldsByEntity[$entity->id ?? 0] ?? [];
            foreach ($fieldKeys as $key) {
                $row[] = $fields[$key] ?? '';
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $response = $this->response->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="records.csv"');

        $response->getBody()->write($csv);

        return $response;
    }

    /**
     * @param list<Entity> $entities
     * @param list<string> $fieldKeys
     * @param array<int, array<string, string>> $fieldsByEntity
     */
    private function jsonResponse(array $entities, array $fieldKeys = [], array $fieldsByEntity = []): ResponseInterface
    {
        $items = array_map(static function (Entity $entity) use ($fieldKeys, $fieldsByEntity): array {
            $fields = $fieldsByEntity[$entity->id ?? 0] ?? [];
            $textFields = [];
            foreach ($fieldKeys as $key) {
                $textFields[$key] = $fields[$key] ?? null;
            }

            return [
                'id' => $entity->id,
                'slug' => $entity->slug,
                'status' => $entity->status->value,
                'published_at' => $entity->publishedAt?->format(\DateTimeInterface::ATOM),
                'text_fields' => $textFields,
            ];
        }, $entities);

        $body = json_encode(['items' => $items, 'total' => count($items)], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);

        $response = $this->response->createResponse(200)
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($body);

        return $response;
    }
}
