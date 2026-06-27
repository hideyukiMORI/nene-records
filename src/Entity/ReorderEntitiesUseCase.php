<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

/**
 * Persist a manual sibling order (#659): assign `menu_order = position` to each
 * record id in the given order. The frontend sends the full reordered sibling
 * list (after a drag / up-down). Org scoping comes from the repository, so ids
 * outside the caller's org are silently no-ops.
 */
final readonly class ReorderEntitiesUseCase implements ReorderEntitiesUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(ReorderEntitiesInput $input): ReorderEntitiesOutput
    {
        $position = 0;
        foreach ($input->ids as $id) {
            $this->entities->updateMenuOrder($id, $position);
            $position++;
        }

        return new ReorderEntitiesOutput(count($input->ids));
    }
}
