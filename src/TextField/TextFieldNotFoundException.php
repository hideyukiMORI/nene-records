<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use RuntimeException;

final class TextFieldNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Text field with id {$id} was not found.");
    }
}
