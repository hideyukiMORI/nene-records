<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use InvalidArgumentException;
use NeNeRecords\UrlRedirect\UrlRedirectRepositoryInterface;

/**
 * Move a custom-permalink record AND its whole subtree under a new parent path
 * (#659). Rewrites the record's permalink prefix and every descendant's, and
 * records a 301 for each changed path (reusing #651's redirect store) so existing
 * links / search results follow the move — no descendant-URL avalanche.
 *
 * Collisions with records OUTSIDE the moving subtree are validated up front, so a
 * clash aborts before any write rather than mid-cascade. Org scoping comes from
 * the injected repositories.
 */
final readonly class MoveEntitySubtreeUseCase implements MoveEntitySubtreeUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        /** Records a 301 from each old path on move (#651 store); optional like UpdateEntity. */
        private ?UrlRedirectRepositoryInterface $redirects = null,
    ) {
    }

    public function execute(MoveEntitySubtreeInput $input): MoveEntitySubtreeOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null || $entity->id === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $oldPermalink = $entity->permalink;

        if ($oldPermalink === null || $oldPermalink === '') {
            throw new InvalidArgumentException('Only a record with a custom permalink can be moved.');
        }

        $newPermalink = $input->newPermalink;

        if ($newPermalink === $oldPermalink) {
            // Dropped onto its current parent — nothing to do.
            return new MoveEntitySubtreeOutput($entity->id, $oldPermalink, 0);
        }

        // A node cannot move into its own subtree (that would orphan it).
        if (str_starts_with($newPermalink, $oldPermalink . '/')) {
            throw new InvalidArgumentException('A page cannot be moved inside its own subtree.');
        }

        $descendants = $this->entities->findByPermalinkPrefix($oldPermalink);

        $subtreeIds = [$entity->id];
        foreach ($descendants as $descendant) {
            if ($descendant->id !== null) {
                $subtreeIds[] = $descendant->id;
            }
        }

        // Plan every (id, old, new) rewrite, then validate collisions against
        // records outside the subtree before touching anything.
        /** @var list<array{id: int, old: string, new: string}> $moves */
        $moves = [['id' => $entity->id, 'old' => $oldPermalink, 'new' => $newPermalink]];
        foreach ($descendants as $descendant) {
            if ($descendant->id === null || $descendant->permalink === null) {
                continue;
            }
            $moves[] = [
                'id' => $descendant->id,
                'old' => $descendant->permalink,
                'new' => $newPermalink . substr($descendant->permalink, strlen($oldPermalink)),
            ];
        }

        foreach ($moves as $move) {
            $occupant = $this->entities->findByPermalink($move['new']);
            if ($occupant !== null && !in_array($occupant->id, $subtreeIds, true)) {
                throw new DuplicateEntityPermalinkException($move['new']);
            }
        }

        foreach ($moves as $move) {
            $this->entities->updatePermalink($move['id'], $move['new']);
            $this->redirects?->save($move['old'], $move['new']);
        }

        return new MoveEntitySubtreeOutput($entity->id, $newPermalink, count($moves));
    }
}
