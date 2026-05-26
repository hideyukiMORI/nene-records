<?php

declare(strict_types=1);

namespace NeNeRecords\Mail;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class SymfonyMailer implements MailerInterface
{
    private Mailer $mailer;

    public function __construct(
        private string $dsn,
        private string $fromAddress,
        private string $fromName,
    ) {
        $transport = Transport::fromDsn($this->dsn);
        $this->mailer = new Mailer($transport);
    }

    public function send(MailMessage $message): void
    {
        $email = (new Email())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($message->to)
            ->subject($message->subject)
            ->text($message->textBody)
            ->html($message->htmlBody);

        $this->mailer->send($email);
    }
}
