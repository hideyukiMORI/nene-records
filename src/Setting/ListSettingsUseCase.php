<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class ListSettingsUseCase implements ListSettingsUseCaseInterface
{
    public function __construct(
        private SettingRepositoryInterface $settings,
    ) {
    }

    public function execute(): ListSettingsOutput
    {
        return new ListSettingsOutput(items: $this->settings->findAllEntries());
    }
}
