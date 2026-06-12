<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use RuntimeException;

final readonly class CreateWidgetUseCase implements CreateWidgetUseCaseInterface
{
    public function __construct(
        private WidgetRepositoryInterface $repository,
    ) {
    }

    public function execute(CreateWidgetInput $input): CreateWidgetOutput
    {
        $id = $this->repository->save(new Widget(
            id: null,
            widgetType: $input->widgetType,
            region: $input->region,
            displayOrder: $input->displayOrder,
            title: $input->title,
            settings: $input->settings,
            createdAt: '',
            updatedAt: '',
        ));

        $saved = $this->repository->findById($id);

        if ($saved === null) {
            throw new RuntimeException('Failed to persist widget.');
        }

        return new CreateWidgetOutput(item: $saved);
    }
}
