<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\BlocksField\BlocksDocumentValidator;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;

final readonly class UpdateSettingUseCase implements UpdateSettingUseCaseInterface
{
    /** Public settings whose JSON value is a typed-block document (#486), server-validated. */
    private const BLOCKS_DOCUMENT_SETTINGS = ['home_hero'];

    /** The setting that pins a single record as the public front page (#701). */
    private const FRONT_PAGE_SETTING = 'front_page';

    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $orgId,
        private EntityRepositoryInterface $entities,
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

        // The front page pins a record id; keep the invariant that it only ever points
        // at an existing, published, non-deleted record in this org (empty = unset).
        if ($input->settingKey === self::FRONT_PAGE_SETTING) {
            $this->validateFrontPage($input->value);
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

    /**
     * @throws SettingValueInvalidException when a non-empty value is not the id of an
     *         existing, published, non-deleted record in the current org.
     */
    private function validateFrontPage(string $value): void
    {
        if ($value === '') {
            return;
        }

        if (!ctype_digit($value)) {
            throw new SettingValueInvalidException('Front page must be a record id.');
        }

        // findById is already org-scoped and excludes soft-deleted records.
        $entity = $this->entities->findById((int) $value);

        if ($entity === null) {
            throw new SettingValueInvalidException('Front page record does not exist.');
        }

        if ($entity->status !== EntityStatus::Published) {
            throw new SettingValueInvalidException('Front page record must be published.');
        }
    }
}
