<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\UserInvite;

use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\MailMessage;

/**
 * Test double that records sent messages without actually sending them.
 */
final class NullMailer implements MailerInterface
{
    /** @var list<MailMessage> */
    public array $sent = [];

    public function send(MailMessage $message): void
    {
        $this->sent[] = $message;
    }
}
