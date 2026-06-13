<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

interface UpdateMenuUseCaseInterface
{
    public function execute(UpdateMenuInput $input): UpdateMenuOutput;
}
