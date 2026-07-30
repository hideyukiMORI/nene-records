<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

/**
 * Stands in until the upstream contract is settled (#1031 §6: contact's ingest wants a numeric
 * `contact_form_id`, records only holds the public form key — the two do not meet yet).
 *
 * It fails, visibly and on purpose. The alternative — pretending the message was delivered —
 * would lose real enquiries silently, which is the one outcome a contact form must never have.
 * Swapping in the real sender is a one-line container change; nothing else knows the difference.
 */
final readonly class UnconfiguredContactSubmissionSender implements ContactSubmissionSenderInterface
{
    public function send(string $formKey, array $values, bool $consent): ContactSubmissionSendResult
    {
        return ContactSubmissionSendResult::failed('Upstream submission is not configured yet (#1031).');
    }
}
