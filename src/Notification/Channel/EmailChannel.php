<?php

declare(strict_types=1);

namespace NeNeRecords\Notification\Channel;

use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\MailMessage;
use NeNeRecords\Notification\NotificationChannelInterface;
use NeNeRecords\Notification\NotificationMessage;
use Throwable;

/**
 * Email notification channel.
 *
 * config_json keys:
 *   to_address (string, required) — recipient email address
 */
final readonly class EmailChannel implements NotificationChannelInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function send(NotificationMessage $message, array $config): void
    {
        try {
            $to = isset($config['to_address']) && is_string($config['to_address'])
                ? $config['to_address']
                : '';

            if ($to === '') {
                return;
            }

            $urlLine = $message->url !== null ? "\n\nURL: {$message->url}" : '';
            $urlHtml = $message->url !== null
                ? '<p><a href="' . htmlspecialchars($message->url, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">View →</a></p>'
                : '';

            $this->mailer->send(new MailMessage(
                to: $to,
                subject: "[NeNe Records] {$message->title}",
                textBody: "{$message->title}\n\n{$message->body}{$urlLine}",
                htmlBody: '<p><strong>' . htmlspecialchars($message->title, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</strong></p>'
                    . '<p>' . nl2br(htmlspecialchars($message->body, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</p>'
                    . $urlHtml,
            ));
        } catch (Throwable) {
            // fire-and-forget
        }
    }
}
