<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\BlocksField\BlocksDocumentValidator;
use NeNeRecords\PublicRecord\FrontPageSetting;

final readonly class UpdateSettingUseCase implements UpdateSettingUseCaseInterface
{
    /** Public settings whose JSON value is a typed-block document (#486), server-validated. */
    private const BLOCKS_DOCUMENT_SETTINGS = ['home_hero'];

    /** The setting that pins a single record as the public front page (#701). */
    private const FRONT_PAGE_SETTING = 'front_page';

    /** The first-party floating CTA config (#982), server-validated then rendered as chrome. */
    private const FLOATING_CTA_SETTING = 'floating_cta';

    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $orgId,
        private FrontPageSetting $frontPage,
        private ClockInterface $clock,
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

        // The floating CTA (#982) is opaque `text` to the generic validator; enforce
        // its P1-safe JSON contract here (fail-closed enums + href scheme allowlist)
        // before it is emitted verbatim into the public shell.
        if ($input->settingKey === self::FLOATING_CTA_SETTING) {
            (new FloatingCtaValidator())->validate($input->value);
        }

        // The front page pins a record id; keep the invariant that it only ever points
        // at an existing, published, non-deleted record in this org (empty = unset).
        if ($input->settingKey === self::FRONT_PAGE_SETTING) {
            $this->frontPage->assertPinnable($input->value);
        }

        $orgId = $this->orgId;
        $clock = $this->clock;
        $stored = $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input, $orgId, $clock): SettingValue {
                $repository = new PdoSettingRepository($query, $orgId, $clock);

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
