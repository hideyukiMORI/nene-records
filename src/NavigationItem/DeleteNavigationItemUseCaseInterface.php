<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

interface DeleteNavigationItemUseCaseInterface
{
    public function execute(int $id): void;
}
