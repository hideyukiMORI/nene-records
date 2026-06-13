<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

interface ListMenusUseCaseInterface
{
    public function execute(): ListMenusOutput;
}
