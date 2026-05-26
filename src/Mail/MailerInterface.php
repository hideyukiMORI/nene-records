<?php

declare(strict_types=1);

namespace NeNeRecords\Mail;

interface MailerInterface
{
    public function send(MailMessage $message): void;
}
