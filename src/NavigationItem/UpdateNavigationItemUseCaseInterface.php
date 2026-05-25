<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

interface UpdateNavigationItemUseCaseInterface
{
    public function execute(UpdateNavigationItemInput $input): UpdateNavigationItemOutput;
}
