<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

interface FindMediaUsagesUseCaseInterface
{
    /**
     * @return list<MediaUsage>
     */
    public function execute(int $id): array;
}
