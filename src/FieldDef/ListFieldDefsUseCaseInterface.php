<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

interface ListFieldDefsUseCaseInterface
{
    public function execute(ListFieldDefsInput $input): ListFieldDefsOutput;
}
