<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

interface CreateNavigationItemUseCaseInterface
{
    public function execute(CreateNavigationItemInput $input): CreateNavigationItemOutput;
}
