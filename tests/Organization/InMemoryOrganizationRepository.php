<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Organization\Organization;
use NeNeRecords\Organization\OrganizationNotFoundException;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\Organization\OrganizationSlugConflictException;

final class InMemoryOrganizationRepository implements OrganizationRepositoryInterface
{
    /** @var array<int, Organization> */
    private array $store = [];

    private int $nextId = 1;

    public function findById(int $id): ?Organization
    {
        return $this->store[$id] ?? null;
    }

    public function findBySlug(string $slug): ?Organization
    {
        foreach ($this->store as $org) {
            if ($org->slug === $slug) {
                return $org;
            }
        }

        return null;
    }

    public function findByCustomDomain(string $domain): ?Organization
    {
        foreach ($this->store as $org) {
            if ($org->customDomain === $domain) {
                return $org;
            }
        }

        return null;
    }

    /** @return list<Organization> */
    public function findAll(int $limit, int $offset): array
    {
        return array_slice(array_values($this->store), $offset, $limit);
    }

    public function count(): int
    {
        return count($this->store);
    }

    public function save(Organization $organization): int
    {
        foreach ($this->store as $existing) {
            if ($existing->slug === $organization->slug) {
                throw new OrganizationSlugConflictException($organization->slug);
            }
        }

        $id = $this->nextId++;
        $now = date('Y-m-d H:i:s');
        $this->store[$id] = new Organization(
            name: $organization->name,
            slug: $organization->slug,
            plan: $organization->plan,
            isActive: $organization->isActive,
            id: $id,
            customDomain: $organization->customDomain,
            createdAt: $now,
            updatedAt: $now,
        );

        return $id;
    }

    public function update(Organization $organization): void
    {
        if ($organization->id === null || !isset($this->store[$organization->id])) {
            throw new OrganizationNotFoundException($organization->id ?? 0);
        }

        $existing = $this->store[$organization->id];
        $now = date('Y-m-d H:i:s');
        $this->store[$organization->id] = new Organization(
            name: $organization->name,
            slug: $organization->slug,
            plan: $organization->plan,
            isActive: $organization->isActive,
            id: $organization->id,
            customDomain: $organization->customDomain,
            createdAt: $existing->createdAt,
            updatedAt: $now,
        );
    }

    public function delete(int $id): void
    {
        if (!isset($this->store[$id])) {
            throw new OrganizationNotFoundException($id);
        }

        unset($this->store[$id]);
    }
}
