<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Supplies the field definitions of a contact form, fetched from the issuing product.
 *
 * Returns null for every failure — unknown key, network error, malformed response. The
 * renderer turns null into a *visible* notice rather than an empty region, so a mistyped
 * form key looks broken to the author instead of quietly rendering nothing (#1030 / #1031
 * fail-visible rule).
 */
interface ContactFormSchemaProviderInterface
{
    public function schemaFor(string $formKey): ?ContactFormSchema;
}
