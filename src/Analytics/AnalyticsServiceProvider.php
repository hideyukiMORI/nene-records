<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\TextField\TextFieldRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                AccessLogRepositoryInterface::class,
                static function (ContainerInterface $c): AccessLogRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoAccessLogRepository($query, $orgId);
                },
            )
            ->set(
                AnalyticsSaltRepositoryInterface::class,
                static function (ContainerInterface $c): AnalyticsSaltRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    return new PdoAnalyticsSaltRepository($query, $clock);
                },
            )
            ->set(
                GetAccessStatsByDateUseCaseInterface::class,
                static function (ContainerInterface $c): GetAccessStatsByDateUseCaseInterface {
                    $repository = $c->get(AccessLogRepositoryInterface::class);

                    if (!$repository instanceof AccessLogRepositoryInterface) {
                        throw new LogicException('Access log repository service is invalid.');
                    }

                    return new GetAccessStatsByDateUseCase($repository);
                },
            )
            ->set(
                GetAccessStatsByDateHandler::class,
                static function (ContainerInterface $c): GetAccessStatsByDateHandler {
                    $useCase = $c->get(GetAccessStatsByDateUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetAccessStatsByDateUseCaseInterface) {
                        throw new LogicException('GetAccessStatsByDate use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetAccessStatsByDateHandler($useCase, $response);
                },
            )
            ->set(
                GetPopularEntitiesUseCaseInterface::class,
                static function (ContainerInterface $c): GetPopularEntitiesUseCaseInterface {
                    $accessLogs = $c->get(AccessLogRepositoryInterface::class);
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $textFields = $c->get(TextFieldRepositoryInterface::class);

                    if (!$accessLogs instanceof AccessLogRepositoryInterface) {
                        throw new LogicException('Access log repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    return new GetPopularEntitiesUseCase($accessLogs, $entities, $textFields, $clock);
                },
            )
            ->set(
                GetPopularEntitiesHandler::class,
                static function (ContainerInterface $c): GetPopularEntitiesHandler {
                    $useCase = $c->get(GetPopularEntitiesUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetPopularEntitiesUseCaseInterface) {
                        throw new LogicException('GetPopularEntities use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetPopularEntitiesHandler($useCase, $response);
                },
            )
            ->set(
                AccessLogMiddleware::class,
                static function (ContainerInterface $c): AccessLogMiddleware {
                    $repository = $c->get(AccessLogRepositoryInterface::class);
                    $logger = $c->get(LoggerInterface::class);

                    if (!$repository instanceof AccessLogRepositoryInterface) {
                        throw new LogicException('Access log repository service is invalid.');
                    }

                    if (!$logger instanceof LoggerInterface) {
                        throw new LogicException('Logger service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    $settings = $c->get(SettingRepositoryInterface::class);
                    if (!$settings instanceof SettingRepositoryInterface) {
                        throw new LogicException('Setting repository service is invalid.');
                    }

                    $salts = $c->get(AnalyticsSaltRepositoryInterface::class);
                    if (!$salts instanceof AnalyticsSaltRepositoryInterface) {
                        throw new LogicException('Analytics salt repository service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new AccessLogMiddleware($repository, $logger, $clock, $settings, $salts, $orgId);
                },
            )
            ->set(
                'nene-records.route_registrar.analytics',
                static function (ContainerInterface $c): AnalyticsRouteRegistrar {
                    $handler = $c->get(GetAccessStatsByDateHandler::class);
                    $popularHandler = $c->get(GetPopularEntitiesHandler::class);

                    if (!$handler instanceof GetAccessStatsByDateHandler) {
                        throw new LogicException('GetAccessStatsByDate handler service is invalid.');
                    }

                    if (!$popularHandler instanceof GetPopularEntitiesHandler) {
                        throw new LogicException('GetPopularEntities handler service is invalid.');
                    }

                    return new AnalyticsRouteRegistrar($handler, $popularHandler);
                },
            );
    }
}
