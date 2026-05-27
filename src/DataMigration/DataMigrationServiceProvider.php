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
                DataMigrationRepositoryInterface::class,
                static function (ContainerInterface $c): DataMigrationRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('DatabaseQueryExecutorInterface is invalid.');
                    }

                    return new PdoDataMigrationRepository($query);
                },
            )
            ->set(
                GetDataMigrationStatusHandler::class,
                static function (ContainerInterface $c): GetDataMigrationStatusHandler {
                    $repo = $c->get(DataMigrationRepositoryInterface::class);
                    $json = $c->get(JsonResponseFactory::class);
                    if (!$repo instanceof DataMigrationRepositoryInterface || !$json instanceof JsonResponseFactory) {
                        throw new LogicException('GetDataMigrationStatusHandler dependencies are invalid.');
                    }

                    return new GetDataMigrationStatusHandler($repo, $json);
                },
            )
            ->set(
                AssignOrganizationHandler::class,
                static function (ContainerInterface $c): AssignOrganizationHandler {
                    $repo    = $c->get(DataMigrationRepositoryInterface::class);
                    $orgs    = $c->get(OrganizationRepositoryInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    if (
                        !$repo instanceof DataMigrationRepositoryInterface
                        || !$orgs instanceof OrganizationRepositoryInterface
                        || !$json instanceof JsonResponseFactory
                        || !$problem instanceof ProblemDetailsResponseFactory
                    ) {
                        throw new LogicException('AssignOrganizationHandler dependencies are invalid.');
                    }

                    return new AssignOrganizationHandler($repo, $orgs, $json, $problem);
                },
            )
            ->set(
                DataMigrationRouteRegistrar::class,
                static function (ContainerInterface $c): DataMigrationRouteRegistrar {
                    $status = $c->get(GetDataMigrationStatusHandler::class);
                    $assign = $c->get(AssignOrganizationHandler::class);
                    if (!$status instanceof GetDataMigrationStatusHandler || !$assign instanceof AssignOrganizationHandler) {
                        throw new LogicException('DataMigrationRouteRegistrar dependencies are invalid.');
                    }

                    return new DataMigrationRouteRegistrar($status, $assign);
                },
            );
    }
}
