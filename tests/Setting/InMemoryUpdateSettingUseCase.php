<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use NeNeRecords\Setting\UpdateSettingInput;
use NeNeRecords\Setting\UpdateSettingOutput;
use NeNeRecords\Setting\UpdateSettingUseCaseInterface;

final readonly class InMemoryUpdateSettingUseCase implements UpdateSettingUseCaseInterface
{
    public function __construct(
        private InMemorySettingRepository $settings,
    ) {
    }

    public function execute(UpdateSettingInput $input): UpdateSettingOutput
    {
        $stored = $this->settings->applyValueDirect(
            $input->settingKey,
            $input->value,
            $input->actorUserId,
        );

        return new UpdateSettingOutput(
            settingKey: $stored->settingKey,
            value: $stored->value ?? '',
            updatedAt: $stored->updatedAt,
        );
    }
}
