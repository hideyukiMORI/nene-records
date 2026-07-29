<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates `org_connect_tokens` — where an organization stores the connect-token it was
 * *issued by another product* (records-embed 案1 / #1029, epic #1001).
 *
 * records is the **holder**, never the issuer: contact mints the token and owns revocation
 * (`revoked_at` + `isActive`), records only stores it and presents it as a bearer credential
 * on server-to-server calls. Nothing here mints, rotates, or expires a token.
 *
 * **Why a dedicated table rather than a column on `setting_values`**: `PdoOrgExportRepository`
 * enumerates 25 tables explicitly and reads `SELECT *` from each, so a new column on an
 * exported table is exported automatically — a secret parked there would silently ride along
 * into every org export. A table the export never enumerates cannot leak that way, so the
 * export must never learn about this one.
 *
 * The value is stored encrypted (AES-256-GCM, key from `NENE_RECORDS_CONFIG_KEY`): the column
 * holds ciphertext, never the token. `token_hint` keeps only the trailing characters so the
 * admin UI can show *which* token is installed without ever returning the secret.
 */
final class CreateOrgConnectTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('org_connect_tokens')
            ->addColumn('organization_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('service', 'string', ['limit' => 32, 'null' => false, 'comment' => 'Issuing product, e.g. "contact".'])
            ->addColumn('token_ciphertext', 'text', ['null' => false, 'comment' => 'AES-256-GCM envelope (base64). Never the raw token.'])
            ->addColumn('token_hint', 'string', ['limit' => 8, 'null' => false, 'default' => '', 'comment' => 'Trailing characters only, for admin display.'])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id', 'service'], ['unique' => true, 'name' => 'uq_org_connect_tokens_org_service'])
            ->create();
    }
}
