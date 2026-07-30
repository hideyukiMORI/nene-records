<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

final class ConnectTokenHttpMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(ConnectTokenSummary $summary): array
    {
        // Takes a summary, not a row: the token cannot be echoed here because it never
        // reaches this layer. `token_hint` is trailing characters only — enough to answer
        // "is the one I pasted still installed?", useless as a credential.
        return [
            'service'    => $summary->service->value,
            'token_hint' => $summary->hint,
            'created_at' => $summary->createdAt,
            'updated_at' => $summary->updatedAt,
        ];
    }
}
