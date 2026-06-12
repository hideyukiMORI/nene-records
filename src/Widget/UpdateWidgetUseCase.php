<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use RuntimeException;

final readonly class UpdateWidgetUseCase implements UpdateWidgetUseCaseInterface
{
    public function __construct(
        private WidgetRepositoryInterface $repository,
    ) {
    }

    public function execute(UpdateWidgetInput $input): UpdateWidgetOutput
    {
        $existing = $this->repository->findById($input->id);

        if ($existing === null) {
            throw new WidgetNotFoundException($input->id);
        }

        $this->repository->update(new Widget(
            id: $input->id,
            widgetType: $input->widgetType,
            region: $input->region,
            displayOrder: $input->displayOrder,
            title: $input->title,
            settings: $input->settings,
            createdAt: $existing->createdAt,
            updatedAt: '',
        ));

        $saved = $this->repository->findById($input->id);

        if ($saved === null) {
            throw new RuntimeException('Failed to persist widget.');
        }

        return new UpdateWidgetOutput(item: $saved);
    }
}
