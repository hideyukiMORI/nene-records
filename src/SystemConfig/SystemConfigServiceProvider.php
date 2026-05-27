<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class SystemConfigServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                SystemConfigRepositoryInterface::class,
                static function (ContainerInterface $c): SystemConfigRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('DatabaseQueryExecutorInterface is invalid.');
                    }

                    return new PdoSystemConfigRepository($query);
                },
            )
            ->set(
                GetSystemConfigHandler::class,
                static function (ContainerInterface $c): GetSystemConfigHandler {
                    $config = $c->get(SystemConfigRepositoryInterface::class);
                    $json   = $c->get(JsonResponseFactory::class);
                    if (!$config instanceof SystemConfigRepositoryInterface) {
                        throw new LogicException('SystemConfigRepositoryInterface is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    return new GetSystemConfigHandler($config, $json);
                },
            )
            ->set(
                UpdateSystemConfigHandler::class,
                static function (ContainerInterface $c): UpdateSystemConfigHandler {
                    $config  = $c->get(SystemConfigRepositoryInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    if (!$config instanceof SystemConfigRepositoryInterface) {
                        throw new LogicException('SystemConfigRepositoryInterface is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    if (!$problem instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory is invalid.');
                    }

                    return new UpdateSystemConfigHandler($config, $json, $problem);
                },
            )
            ->set(
                SystemConfigRouteRegistrar::class,
                static function (ContainerInterface $c): SystemConfigRouteRegistrar {
                    $get    = $c->get(GetSystemConfigHandler::class);
                    $update = $c->get(UpdateSystemConfigHandler::class);
                    if (!$get instanceof GetSystemConfigHandler) {
                        throw new LogicException('GetSystemConfigHandler is invalid.');
                    }

                    if (!$update instanceof UpdateSystemConfigHandler) {
                        throw new LogicException('UpdateSystemConfigHandler is invalid.');
                    }

                    return new SystemConfigRouteRegistrar($get, $update);
                },
            );
    }
}
