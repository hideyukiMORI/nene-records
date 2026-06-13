<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

interface DeleteMenuUseCaseInterface
{
    public function execute(DeleteMenuInput $input): void;
}
