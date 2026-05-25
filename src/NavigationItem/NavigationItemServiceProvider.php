<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class NavigationItemServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                NavigationItemRepositoryInterface::class,
                static function (ContainerInterface $container): NavigationItemRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoNavigationItemRepository($query);
                },
            )
            ->set(
                ListNavigationItemsUseCaseInterface::class,
                static function (ContainerInterface $container): ListNavigationItemsUseCaseInterface {
                    $repo = $container->get(NavigationItemRepositoryInterface::class);

                    if (!$repo instanceof NavigationItemRepositoryInterface) {
                        throw new LogicException('NavigationItem repository service is invalid.');
                    }

                    return new ListNavigationItemsUseCase($repo);
                },
            )
            ->set(
                CreateNavigationItemUseCaseInterface::class,
                static function (ContainerInterface $container): CreateNavigationItemUseCaseInterface {
                    $repo = $container->get(NavigationItemRepositoryInterface::class);

                    if (!$repo instanceof NavigationItemRepositoryInterface) {
                        throw new LogicException('NavigationItem repository service is invalid.');
                    }

                    return new CreateNavigationItemUseCase($repo);
                },
            )
            ->set(
                UpdateNavigationItemUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateNavigationItemUseCaseInterface {
                    $repo = $container->get(NavigationItemRepositoryInterface::class);

                    if (!$repo instanceof NavigationItemRepositoryInterface) {
                        throw new LogicException('NavigationItem repository service is invalid.');
                    }

                    return new UpdateNavigationItemUseCase($repo);
                },
            )
            ->set(
                DeleteNavigationItemUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteNavigationItemUseCaseInterface {
                    $repo = $container->get(NavigationItemRepositoryInterface::class);

                    if (!$repo instanceof NavigationItemRepositoryInterface) {
                        throw new LogicException('NavigationItem repository service is invalid.');
                    }

                    return new DeleteNavigationItemUseCase($repo);
                },
            )
            ->set(
                ListNavigationItemsHandler::class,
                static function (ContainerInterface $container): ListNavigationItemsHandler {
                    $useCase = $container->get(ListNavigationItemsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListNavigationItemsUseCaseInterface) {
                        throw new LogicException('ListNavigationItems use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListNavigationItemsHandler($useCase, $response);
                },
            )
            ->set(
                CreateNavigationItemHandler::class,
                static function (ContainerInterface $container): CreateNavigationItemHandler {
                    $useCase = $container->get(CreateNavigationItemUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateNavigationItemUseCaseInterface) {
                        throw new LogicException('CreateNavigationItem use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateNavigationItemHandler($useCase, $response);
                },
            )
            ->set(
                UpdateNavigationItemHandler::class,
                static function (ContainerInterface $container): UpdateNavigationItemHandler {
                    $useCase = $container->get(UpdateNavigationItemUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateNavigationItemUseCaseInterface) {
                        throw new LogicException('UpdateNavigationItem use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateNavigationItemHandler($useCase, $response);
                },
            )
            ->set(
                DeleteNavigationItemHandler::class,
                static function (ContainerInterface $container): DeleteNavigationItemHandler {
                    $useCase = $container->get(DeleteNavigationItemUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof DeleteNavigationItemUseCaseInterface) {
                        throw new LogicException('DeleteNavigationItem use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteNavigationItemHandler($useCase, $response);
                },
            )
            ->set(
                NavigationItemNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): NavigationItemNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new NavigationItemNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.navigation_item',
                static function (ContainerInterface $container): NavigationItemRouteRegistrar {
                    $list = $container->get(ListNavigationItemsHandler::class);
                    $create = $container->get(CreateNavigationItemHandler::class);
                    $update = $container->get(UpdateNavigationItemHandler::class);
                    $delete = $container->get(DeleteNavigationItemHandler::class);

                    if (!$list instanceof ListNavigationItemsHandler) {
                        throw new LogicException('ListNavigationItems handler service is invalid.');
                    }

                    if (!$create instanceof CreateNavigationItemHandler) {
                        throw new LogicException('CreateNavigationItem handler service is invalid.');
                    }

                    if (!$update instanceof UpdateNavigationItemHandler) {
                        throw new LogicException('UpdateNavigationItem handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteNavigationItemHandler) {
                        throw new LogicException('DeleteNavigationItem handler service is invalid.');
                    }

                    return new NavigationItemRouteRegistrar($list, $create, $update, $delete);
                },
            );
    }
}
