<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class ListSettingRevisionsUseCase implements ListSettingRevisionsUseCaseInterface
{
    public function __construct(
        private SettingRepositoryInterface $settings,
    ) {
    }

    public function execute(ListSettingRevisionsInput $input): ListSettingRevisionsOutput
    {
        if ($this->settings->findDefByKey($input->settingKey) === null) {
            throw new SettingKeyNotFoundException($input->settingKey);
        }

        return new ListSettingRevisionsOutput(
            items: $this->settings->findRevisionsByKey($input->settingKey, $input->limit, $input->offset),
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
