<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class OrgExportServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrgExportRepositoryInterface::class,
                static function (ContainerInterface $c): OrgExportRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('DatabaseQueryExecutorInterface is invalid.');
                    }

                    return new PdoOrgExportRepository($query);
                },
            )
            ->set(
                OrgImportRepositoryInterface::class,
                static function (ContainerInterface $c): OrgImportRepositoryInterface {
                    $transactions = $c->get(DatabaseTransactionManagerInterface::class);
                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('DatabaseTransactionManagerInterface is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface is invalid.');
                    }

                    return new PdoOrgImportRepository($transactions, $clock);
                },
            )
            ->set(
                ExportOrganizationHandler::class,
                static function (ContainerInterface $c): ExportOrganizationHandler {
                    $repo    = $c->get(OrgExportRepositoryInterface::class);
                    $orgs    = $c->get(OrganizationRepositoryInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    $clock   = $c->get(ClockInterface::class);
                    if (
                        !$repo instanceof OrgExportRepositoryInterface
                        || !$orgs instanceof OrganizationRepositoryInterface
                        || !$json instanceof JsonResponseFactory
                        || !$problem instanceof ProblemDetailsResponseFactory
                        || !$clock instanceof ClockInterface
                    ) {
                        throw new LogicException('ExportOrganizationHandler dependencies are invalid.');
                    }

                    return new ExportOrganizationHandler($repo, $orgs, $json, $problem, $clock);
                },
            )
            ->set(
                ImportOrganizationHandler::class,
                static function (ContainerInterface $c): ImportOrganizationHandler {
                    $repo    = $c->get(OrgImportRepositoryInterface::class);
                    $orgs    = $c->get(OrganizationRepositoryInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    if (
                        !$repo instanceof OrgImportRepositoryInterface
                        || !$orgs instanceof OrganizationRepositoryInterface
                        || !$json instanceof JsonResponseFactory
                        || !$problem instanceof ProblemDetailsResponseFactory
                    ) {
                        throw new LogicException('ImportOrganizationHandler dependencies are invalid.');
                    }

                    return new ImportOrganizationHandler($repo, $orgs, $json, $problem);
                },
            )
            ->set(
                OrgExportRouteRegistrar::class,
                static function (ContainerInterface $c): OrgExportRouteRegistrar {
                    $export = $c->get(ExportOrganizationHandler::class);
                    $import = $c->get(ImportOrganizationHandler::class);
                    if (
                        !$export instanceof ExportOrganizationHandler
                        || !$import instanceof ImportOrganizationHandler
                    ) {
                        throw new LogicException('OrgExportRouteRegistrar dependencies are invalid.');
                    }

                    return new OrgExportRouteRegistrar($export, $import);
                },
            );
    }
}
