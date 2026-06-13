<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

interface CreateMenuUseCaseInterface
{
    public function execute(CreateMenuInput $input): CreateMenuOutput;
}
