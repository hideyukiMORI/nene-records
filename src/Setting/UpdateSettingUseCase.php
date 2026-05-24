<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;

final readonly class UpdateSettingUseCase implements UpdateSettingUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(UpdateSettingInput $input): UpdateSettingOutput
    {
        $stored = $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): SettingValue {
                $repository = new PdoSettingRepository($query);

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
