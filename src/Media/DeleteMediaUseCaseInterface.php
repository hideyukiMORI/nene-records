<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

interface DeleteMediaUseCaseInterface
{
    public function execute(DeleteMediaInput $input): void;
}
