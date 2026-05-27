<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

final readonly class GetEntityArchiveCsvUseCase implements GetEntityArchiveCsvUseCaseInterface
{
    private const int PAGE_SIZE = 500;

    public function __construct(
        private EntityArchiveRepositoryInterface $archive,
    ) {
    }

    public function execute(GetEntityArchiveCsvInput $input): GetEntityArchiveCsvOutput
    {
        $rows = [];
        $offset = 0;

        do {
            $entries = $this->archive->findByEntityTypeId($input->entityTypeId, self::PAGE_SIZE, $offset);

            foreach ($entries as $entry) {
                $rows[] = $entry;
            }

            $offset += self::PAGE_SIZE;
        } while (count($entries) === self::PAGE_SIZE);

        return new GetEntityArchiveCsvOutput(
            entityTypeId: $input->entityTypeId,
            rows: $rows,
        );
    }
}
