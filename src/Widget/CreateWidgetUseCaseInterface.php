<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

interface CreateWidgetUseCaseInterface
{
    public function execute(CreateWidgetInput $input): CreateWidgetOutput;
}
