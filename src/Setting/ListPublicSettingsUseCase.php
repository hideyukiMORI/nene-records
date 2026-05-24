<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class ListPublicSettingsUseCase implements ListPublicSettingsUseCaseInterface
{
    public function __construct(
        private SettingRepositoryInterface $settings,
    ) {
    }

    public function execute(): ListPublicSettingsOutput
    {
        return new ListPublicSettingsOutput(items: $this->settings->findPublicEntries());
    }
}
