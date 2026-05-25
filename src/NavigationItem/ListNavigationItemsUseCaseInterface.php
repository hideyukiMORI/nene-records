<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

interface ListNavigationItemsUseCaseInterface
{
    public function execute(): ListNavigationItemsOutput;
}
