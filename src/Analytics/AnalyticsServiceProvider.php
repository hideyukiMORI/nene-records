<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
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

                    return new PdoAccessLogRepository($query);
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

                    return new AccessLogMiddleware($repository, $logger);
                },
            )
            ->set(
                'nene-records.route_registrar.analytics',
                static function (ContainerInterface $c): AnalyticsRouteRegistrar {
                    $handler = $c->get(GetAccessStatsByDateHandler::class);

                    if (!$handler instanceof GetAccessStatsByDateHandler) {
                        throw new LogicException('GetAccessStatsByDate handler service is invalid.');
                    }

                    return new AnalyticsRouteRegistrar($handler);
                },
            );
    }
}
