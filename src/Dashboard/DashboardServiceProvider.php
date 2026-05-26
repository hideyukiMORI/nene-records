<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Analytics\AccessLogRepositoryInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class DashboardServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                GetDashboardSummaryUseCaseInterface::class,
                static function (ContainerInterface $container): GetDashboardSummaryUseCaseInterface {
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $entityTypes = $container->get(EntityTypeRepositoryInterface::class);
                    $accessLogs = $container->get(AccessLogRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('EntityType repository service is invalid.');
                    }

                    if (!$accessLogs instanceof AccessLogRepositoryInterface) {
                        throw new LogicException('Access log repository service is invalid.');
                    }

                    return new GetDashboardSummaryUseCase($entities, $entityTypes, $accessLogs);
                },
            )
            ->set(
                GetDashboardSummaryHandler::class,
                static function (ContainerInterface $container): GetDashboardSummaryHandler {
                    $useCase = $container->get(GetDashboardSummaryUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetDashboardSummaryUseCaseInterface) {
                        throw new LogicException('GetDashboardSummary use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetDashboardSummaryHandler($useCase, $response);
                },
            )
            ->set(
                'nene-records.route_registrar.dashboard',
                static function (ContainerInterface $container): DashboardRouteRegistrar {
                    $handler = $container->get(GetDashboardSummaryHandler::class);

                    if (!$handler instanceof GetDashboardSummaryHandler) {
                        throw new LogicException('GetDashboardSummary handler service is invalid.');
                    }

                    return new DashboardRouteRegistrar($handler);
                },
            );
    }
}
