<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

interface DeleteWidgetUseCaseInterface
{
    public function execute(DeleteWidgetInput $input): void;
}
