<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

interface UpdateMediaAltUseCaseInterface
{
    public function execute(UpdateMediaAltInput $input): Media;
}
