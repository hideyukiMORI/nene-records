<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

interface MediaRepositoryInterface
{
    public function save(Media $media): int;

    public function findById(int $id): ?Media;
}
