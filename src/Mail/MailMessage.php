<?php

declare(strict_types=1);

namespace NeNeRecords\Mail;

final readonly class MailMessage
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $textBody,
        public string $htmlBody,
    ) {
    }
}
