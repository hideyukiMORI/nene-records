<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

interface UploadMediaUseCaseInterface
{
    public function execute(UploadMediaInput $input): UploadMediaOutput;
}
