<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
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
                GetSystemConfigUseCaseInterface::class,
                static function (ContainerInterface $c): GetSystemConfigUseCaseInterface {
                    $config = $c->get(SystemConfigRepositoryInterface::class);
                    if (!$config instanceof SystemConfigRepositoryInterface) {
                        throw new LogicException('SystemConfigRepositoryInterface is invalid.');
                    }

                    return new GetSystemConfigUseCase($config);
                },
            )
            ->set(
                UpdateSystemConfigUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateSystemConfigUseCaseInterface {
                    $config = $c->get(SystemConfigRepositoryInterface::class);
                    if (!$config instanceof SystemConfigRepositoryInterface) {
                        throw new LogicException('SystemConfigRepositoryInterface is invalid.');
                    }

                    return new UpdateSystemConfigUseCase($config);
                },
            )
            ->set(
                GetSystemConfigHandler::class,
                static function (ContainerInterface $c): GetSystemConfigHandler {
                    $useCase = $c->get(GetSystemConfigUseCaseInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof GetSystemConfigUseCaseInterface) {
                        throw new LogicException('GetSystemConfigUseCaseInterface is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    return new GetSystemConfigHandler($useCase, $json);
                },
            )
            ->set(
                UpdateSystemConfigHandler::class,
                static function (ContainerInterface $c): UpdateSystemConfigHandler {
                    $useCase = $c->get(UpdateSystemConfigUseCaseInterface::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof UpdateSystemConfigUseCaseInterface) {
                        throw new LogicException('UpdateSystemConfigUseCaseInterface is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    return new UpdateSystemConfigHandler($useCase, $json);
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
