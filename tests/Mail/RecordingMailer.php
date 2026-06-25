<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Mail;

use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\MailMessage;

/** Test double: records every sent message for assertions. */
final class RecordingMailer implements MailerInterface
{
    /** @var list<MailMessage> */
    public array $sent = [];

    public function send(MailMessage $message): void
    {
        $this->sent[] = $message;
    }
}
