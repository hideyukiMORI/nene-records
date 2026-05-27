<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class DataMigrationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DataMigrationRepository::class,
                static function (ContainerInterface $c): DataMigrationRepository {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('DatabaseQueryExecutorInterface is invalid.');
                    }

                    return new DataMigrationRepository($query);
                },
            )
            ->set(
                DataMigrationStatusHandler::class,
                static function (ContainerInterface $c): DataMigrationStatusHandler {
                    $repo = $c->get(DataMigrationRepository::class);
                    $json = $c->get(JsonResponseFactory::class);
                    if (!$repo instanceof DataMigrationRepository || !$json instanceof JsonResponseFactory) {
                        throw new LogicException('DataMigrationStatusHandler dependencies are invalid.');
                    }

                    return new DataMigrationStatusHandler($repo, $json);
                },
            )
            ->set(
                AssignOrgHandler::class,
                static function (ContainerInterface $c): AssignOrgHandler {
                    $repo    = $c->get(DataMigrationRepository::class);
                    $orgs    = $c->get(OrganizationRepositoryInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    if (
                        !$repo instanceof DataMigrationRepository
                        || !$orgs instanceof OrganizationRepositoryInterface
                        || !$json instanceof JsonResponseFactory
                        || !$problem instanceof ProblemDetailsResponseFactory
                    ) {
                        throw new LogicException('AssignOrgHandler dependencies are invalid.');
                    }

                    return new AssignOrgHandler($repo, $orgs, $json, $problem);
                },
            )
            ->set(
                DataMigrationRouteRegistrar::class,
                static function (ContainerInterface $c): DataMigrationRouteRegistrar {
                    $status = $c->get(DataMigrationStatusHandler::class);
                    $assign = $c->get(AssignOrgHandler::class);
                    if (!$status instanceof DataMigrationStatusHandler || !$assign instanceof AssignOrgHandler) {
                        throw new LogicException('DataMigrationRouteRegistrar dependencies are invalid.');
                    }

                    return new DataMigrationRouteRegistrar($status, $assign);
                },
            );
    }
}
