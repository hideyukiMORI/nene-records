<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class OrgExportServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrgExportRepository::class,
                static function (ContainerInterface $c): OrgExportRepository {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('DatabaseQueryExecutorInterface is invalid.');
                    }

                    return new OrgExportRepository($query);
                },
            )
            ->set(
                OrgImportRepository::class,
                static function (ContainerInterface $c): OrgImportRepository {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('DatabaseQueryExecutorInterface is invalid.');
                    }

                    return new OrgImportRepository($query);
                },
            )
            ->set(
                OrgExportHandler::class,
                static function (ContainerInterface $c): OrgExportHandler {
                    $repo    = $c->get(OrgExportRepository::class);
                    $orgs    = $c->get(OrganizationRepositoryInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    if (
                        !$repo instanceof OrgExportRepository
                        || !$orgs instanceof OrganizationRepositoryInterface
                        || !$json instanceof JsonResponseFactory
                        || !$problem instanceof ProblemDetailsResponseFactory
                    ) {
                        throw new LogicException('OrgExportHandler dependencies are invalid.');
                    }

                    return new OrgExportHandler($repo, $orgs, $json, $problem);
                },
            )
            ->set(
                OrgImportHandler::class,
                static function (ContainerInterface $c): OrgImportHandler {
                    $repo    = $c->get(OrgImportRepository::class);
                    $orgs    = $c->get(OrganizationRepositoryInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    if (
                        !$repo instanceof OrgImportRepository
                        || !$orgs instanceof OrganizationRepositoryInterface
                        || !$json instanceof JsonResponseFactory
                        || !$problem instanceof ProblemDetailsResponseFactory
                    ) {
                        throw new LogicException('OrgImportHandler dependencies are invalid.');
                    }

                    return new OrgImportHandler($repo, $orgs, $json, $problem);
                },
            )
            ->set(
                OrgExportRouteRegistrar::class,
                static function (ContainerInterface $c): OrgExportRouteRegistrar {
                    $export = $c->get(OrgExportHandler::class);
                    $import = $c->get(OrgImportHandler::class);
                    if (
                        !$export instanceof OrgExportHandler
                        || !$import instanceof OrgImportHandler
                    ) {
                        throw new LogicException('OrgExportRouteRegistrar dependencies are invalid.');
                    }

                    return new OrgExportRouteRegistrar($export, $import);
                },
            );
    }
}
