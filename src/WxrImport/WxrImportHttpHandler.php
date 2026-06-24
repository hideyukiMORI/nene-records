<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * WordPress WXR import endpoint (admin-only).
 *
 * POST /api/v1/migration/wxr  (multipart/form-data)
 *   - `file`    : the WordPress WXR export (.xml) — required
 *   - `dry_run` : "true" (default) returns a preview plan without writing;
 *                 "false" executes the import into the active organization.
 */
final readonly class WxrImportHttpHandler implements RequestHandlerInterface
{
    public function __construct(
        private WxrImportExecutor $executor,
        private WxrMediaImporter $mediaImporter,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $file = $request->getUploadedFiles()['file'] ?? null;

        if (!$file instanceof UploadedFileInterface) {
            return $this->problemDetails->create(
                $request,
                'wxr-no-file',
                'No WXR File',
                422,
                'multipart field "file" (a WordPress WXR export) is required.',
            );
        }

        $xml = (string) $file->getStream();

        $body = $request->getParsedBody();
        $dryRun = !is_array($body) || ($body['dry_run'] ?? 'true') !== 'false';

        try {
            $document = (new WxrParser())->parse($xml);
        } catch (WxrParseException $exception) {
            return $this->problemDetails->create(
                $request,
                'wxr-parse-failed',
                'WXR Parse Failed',
                422,
                $exception->getMessage(),
            );
        }

        if ($dryRun) {
            return $this->json->create($this->planToArray((new WxrImportPlanner())->plan($document)));
        }

        // Import attachments first so the entity importer can rewrite body image
        // URLs from the old WordPress media to the new media library.
        $media = $this->mediaImporter->importAttachments($document);
        $result = $this->executor->execute($document, $media->urlMap);

        return $this->json->create($this->resultToArray($result, $media), 201);
    }

    /** @return array<string, mixed> */
    private function planToArray(WxrImportPlan $plan): array
    {
        return [
            'mode' => 'preview',
            'planned_count' => count($plan->plannedItems),
            'skipped_count' => count($plan->skippedItems),
            'counts_by_entity_type' => $plan->countsByEntityType,
            'counts_by_status' => $plan->countsByStatus,
            'tags' => $plan->tagSlugs,
            'warnings' => $plan->warnings,
            'planned' => array_map(static fn (WxrImportPlannedItem $i): array => [
                'title' => $i->title,
                'slug' => $i->slug,
                'entity_type' => $i->entityTypeSlug,
                'status' => $i->status,
                'tags' => $i->tagSlugs,
            ], $plan->plannedItems),
            'skipped' => array_map(static fn (WxrImportSkippedItem $s): array => [
                'title' => $s->title,
                'reason' => $s->reason,
            ], $plan->skippedItems),
        ];
    }

    /** @return array<string, mixed> */
    private function resultToArray(WxrImportResult $result, WxrMediaImportResult $media): array
    {
        return [
            'mode' => 'import',
            'created_entities' => $result->createdEntities,
            'skipped_existing' => $result->skippedExisting,
            'tags_ensured' => $result->tagsEnsured,
            'tag_links' => $result->tagLinks,
            'redirects_created' => $result->redirectsCreated,
            'media_imported' => $media->imported,
            'media_skipped' => $media->skipped,
            'skipped' => array_map(static fn (WxrImportSkippedItem $s): array => [
                'title' => $s->title,
                'reason' => $s->reason,
            ], $result->skippedItems),
            'warnings' => $result->warnings,
        ];
    }
}
