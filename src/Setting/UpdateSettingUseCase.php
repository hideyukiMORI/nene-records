<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\BlocksField\BlocksDocumentValidator;

final readonly class UpdateSettingUseCase implements UpdateSettingUseCaseInterface
{
    /** Public settings whose JSON value is a typed-block document (#486), server-validated. */
    private const BLOCKS_DOCUMENT_SETTINGS = ['home_hero'];

    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $orgId,
    ) {
    }

    public function execute(UpdateSettingInput $input): UpdateSettingOutput
    {
        // Block-document settings are opaque `text` to the generic validator, so
        // enforce the typed-block contract here (the trust boundary). Throws
        // ValidationException → 422 for malformed/unsafe documents.
        if (in_array($input->settingKey, self::BLOCKS_DOCUMENT_SETTINGS, true)) {
            (new BlocksDocumentValidator())->validate($input->value);
        }

        $orgId = $this->orgId;
        $stored = $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input, $orgId): SettingValue {
                $repository = new PdoSettingRepository($query, $orgId);

                return $repository->applyValue($input->settingKey, $input->value, $input->actorUserId);
            },
        );

        return new UpdateSettingOutput(
            settingKey: $stored->settingKey,
            value: $stored->value ?? '',
            updatedAt: $stored->updatedAt,
        );
    }
}
