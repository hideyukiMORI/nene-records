<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

interface UpdateWidgetUseCaseInterface
{
    public function execute(UpdateWidgetInput $input): UpdateWidgetOutput;
}
