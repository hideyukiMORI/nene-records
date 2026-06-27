<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Bulk 301-redirect CSV import endpoint (admin-only). #651 PR4.
 *
 * POST /api/v1/migration/url-redirects  (multipart/form-data)
 *   - `file`    : a CSV of `source,target` path pairs — required
 *   - `dry_run` : "true" (default) validates + previews without writing;
 *                 "false" upserts the redirects into the active organization.
 */
final readonly class ImportRedirectsCsvHttpHandler implements RequestHandlerInterface
{
    public function __construct(
        private ImportRedirectsCsvUseCase $useCase,
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
                'redirect-csv-no-file',
                'No CSV File',
                422,
                'multipart field "file" (a CSV of source,target paths) is required.',
            );
        }

        $csv = (string) $file->getStream();

        $body = $request->getParsedBody();
        $dryRun = !is_array($body) || ($body['dry_run'] ?? 'true') !== 'false';

        $output = $this->useCase->execute($csv, $dryRun);

        return $this->json->create($this->toArray($output), $dryRun ? 200 : 201);
    }

    /** @return array<string, mixed> */
    private function toArray(ImportRedirectsCsvOutput $output): array
    {
        return [
            'mode' => $output->dryRun ? 'preview' : 'import',
            'total_rows' => $output->totalRows,
            'valid_rows' => $output->validRows,
            'imported_rows' => $output->importedRows,
            'skipped_rows' => $output->skippedRows,
            'errors' => $output->errors,
            'samples' => $output->samples,
        ];
    }
}
