<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class UpdateSettingUseCase implements UpdateSettingUseCaseInterface
{
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
