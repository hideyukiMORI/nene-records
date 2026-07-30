<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

/**
 * Hands one validated submission to the product that owns the form.
 *
 * Separated from the handler so the guards (origin, throttle, caps, schema conformance) can be
 * built and tested before the upstream contract is settled — and so a test can assert that a
 * rejected submission never reaches this interface at all.
 */
interface ContactSubmissionSenderInterface
{
    /**
     * @param non-empty-string      $formKey
     * @param array<string, string> $values  only keys the resolved schema declares
     */
    public function send(string $formKey, array $values, bool $consent): ContactSubmissionSendResult;
}
