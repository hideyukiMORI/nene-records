<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * What one blocks document produced on the server: the HTML, plus the public schemas that
 * were resolved to produce it.
 *
 * The schemas travel with the HTML because the SPA replaces the server-rendered markup on
 * mount (see `#root` in templates/public/record-detail.php). If the client fetched them
 * again it could get a different answer than the crawler saw; handing it the *same* resolved
 * data makes "SSR and SPA render the same form" a structural property rather than a hope —
 * and costs no extra request (#1030).
 */
final readonly class SsrBlocksRenderResult
{
    /**
     * @param array<string, array<string, mixed>> $contactForms form key => public schema, as it
     *                                                          goes into the SSR bootstrap
     */
    public function __construct(
        public string $html,
        public array $contactForms = [],
    ) {
    }
}
