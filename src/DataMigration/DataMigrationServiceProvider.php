<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
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
                GetDataMigrationStatusUseCaseInterface::class,
                static function (ContainerInterface $c): GetDataMigrationStatusUseCaseInterface {
                    $repo = $c->get(DataMigrationRepositoryInterface::class);
                    if (!$repo instanceof DataMigrationRepositoryInterface) {
                        throw new LogicException('DataMigrationRepositoryInterface is invalid.');
                    }

                    return new GetDataMigrationStatusUseCase($repo);
                },
            )
            ->set(
                AssignOrganizationUseCaseInterface::class,
                static function (ContainerInterface $c): AssignOrganizationUseCaseInterface {
                    $repo  = $c->get(DataMigrationRepositoryInterface::class);
                    $orgs  = $c->get(OrganizationRepositoryInterface::class);
                    if (!$repo instanceof DataMigrationRepositoryInterface) {
                        throw new LogicException('DataMigrationRepositoryInterface is invalid.');
                    }

                    if (!$orgs instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('OrganizationRepositoryInterface is invalid.');
                    }

                    return new AssignOrganizationUseCase($repo, $orgs);
                },
            )
            ->set(
                GetDataMigrationStatusHandler::class,
                static function (ContainerInterface $c): GetDataMigrationStatusHandler {
                    $useCase = $c->get(GetDataMigrationStatusUseCaseInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof GetDataMigrationStatusUseCaseInterface) {
                        throw new LogicException('GetDataMigrationStatusUseCaseInterface is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    return new GetDataMigrationStatusHandler($useCase, $json);
                },
            )
            ->set(
                AssignOrganizationHandler::class,
                static function (ContainerInterface $c): AssignOrganizationHandler {
                    $useCase = $c->get(AssignOrganizationUseCaseInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof AssignOrganizationUseCaseInterface) {
                        throw new LogicException('AssignOrganizationUseCaseInterface is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    return new AssignOrganizationHandler($useCase, $json);
                },
            )
            ->set(
                DataMigrationRouteRegistrar::class,
                static function (ContainerInterface $c): DataMigrationRouteRegistrar {
                    $status = $c->get(GetDataMigrationStatusHandler::class);
                    $assign = $c->get(AssignOrganizationHandler::class);
                    if (!$status instanceof GetDataMigrationStatusHandler) {
                        throw new LogicException('GetDataMigrationStatusHandler is invalid.');
                    }

                    if (!$assign instanceof AssignOrganizationHandler) {
                        throw new LogicException('AssignOrganizationHandler is invalid.');
                    }

                    return new DataMigrationRouteRegistrar($status, $assign);
                },
            );
    }
}
