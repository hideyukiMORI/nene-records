<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * The single place the submission endpoint path is written down.
 *
 * The SSR form posts here, and the proxy that will answer it (#1031) registers here. Keeping
 * it in one constant is what lets a test pin "the rendered form posts to records, never to
 * contact directly" without restating the path — a form that posted straight to the issuing
 * product would expose the connect-token to the browser, which is the whole thing this
 * design exists to prevent.
 */
final class ContactSubmissionProxyRoute
{
    public const PATH = '/api/v1/public/contact-submissions';
}
