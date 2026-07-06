<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class MenuServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                MenuRepositoryInterface::class,
                static function (ContainerInterface $container): MenuRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    $clock = $container->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    return new PdoMenuRepository($query, $orgId, $clock);
                },
            )
            ->set(
                ListMenusUseCaseInterface::class,
                static function (ContainerInterface $container): ListMenusUseCaseInterface {
                    $repo = $container->get(MenuRepositoryInterface::class);
                    if (!$repo instanceof MenuRepositoryInterface) {
                        throw new LogicException('Menu repository service is invalid.');
                    }

                    return new ListMenusUseCase($repo);
                },
            )
            ->set(
                CreateMenuUseCaseInterface::class,
                static function (ContainerInterface $container): CreateMenuUseCaseInterface {
                    $repo = $container->get(MenuRepositoryInterface::class);
                    if (!$repo instanceof MenuRepositoryInterface) {
                        throw new LogicException('Menu repository service is invalid.');
                    }

                    return new CreateMenuUseCase($repo);
                },
            )
            ->set(
                UpdateMenuUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateMenuUseCaseInterface {
                    $repo = $container->get(MenuRepositoryInterface::class);
                    if (!$repo instanceof MenuRepositoryInterface) {
                        throw new LogicException('Menu repository service is invalid.');
                    }

                    return new UpdateMenuUseCase($repo);
                },
            )
            ->set(
                DeleteMenuUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteMenuUseCaseInterface {
                    $repo = $container->get(MenuRepositoryInterface::class);
                    if (!$repo instanceof MenuRepositoryInterface) {
                        throw new LogicException('Menu repository service is invalid.');
                    }

                    return new DeleteMenuUseCase($repo);
                },
            )
            ->set(
                ListMenusHandler::class,
                static function (ContainerInterface $container): ListMenusHandler {
                    $useCase = $container->get(ListMenusUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof ListMenusUseCaseInterface) {
                        throw new LogicException('ListMenus use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListMenusHandler($useCase, $response);
                },
            )
            ->set(
                CreateMenuHandler::class,
                static function (ContainerInterface $container): CreateMenuHandler {
                    $useCase = $container->get(CreateMenuUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof CreateMenuUseCaseInterface) {
                        throw new LogicException('CreateMenu use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateMenuHandler($useCase, $response);
                },
            )
            ->set(
                UpdateMenuHandler::class,
                static function (ContainerInterface $container): UpdateMenuHandler {
                    $useCase = $container->get(UpdateMenuUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof UpdateMenuUseCaseInterface) {
                        throw new LogicException('UpdateMenu use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateMenuHandler($useCase, $response);
                },
            )
            ->set(
                DeleteMenuHandler::class,
                static function (ContainerInterface $container): DeleteMenuHandler {
                    $useCase = $container->get(DeleteMenuUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof DeleteMenuUseCaseInterface) {
                        throw new LogicException('DeleteMenu use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteMenuHandler($useCase, $response);
                },
            )
            ->set(
                MenuNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): MenuNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new MenuNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                ListPublicMenusHandler::class,
                static function (ContainerInterface $container): ListPublicMenusHandler {
                    $useCase = $container->get(ListMenusUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    if (!$useCase instanceof ListMenusUseCaseInterface) {
                        throw new LogicException('ListMenus use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }
                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new ListPublicMenusHandler($useCase, $response, $responseFactory);
                },
            )
            ->set(
                'nene-records.route_registrar.menu',
                static function (ContainerInterface $container): MenuRouteRegistrar {
                    $list = $container->get(ListMenusHandler::class);
                    $listPublic = $container->get(ListPublicMenusHandler::class);
                    $create = $container->get(CreateMenuHandler::class);
                    $update = $container->get(UpdateMenuHandler::class);
                    $delete = $container->get(DeleteMenuHandler::class);

                    if (!$list instanceof ListMenusHandler) {
                        throw new LogicException('ListMenus handler service is invalid.');
                    }
                    if (!$listPublic instanceof ListPublicMenusHandler) {
                        throw new LogicException('ListPublicMenus handler service is invalid.');
                    }
                    if (!$create instanceof CreateMenuHandler) {
                        throw new LogicException('CreateMenu handler service is invalid.');
                    }
                    if (!$update instanceof UpdateMenuHandler) {
                        throw new LogicException('UpdateMenu handler service is invalid.');
                    }
                    if (!$delete instanceof DeleteMenuHandler) {
                        throw new LogicException('DeleteMenu handler service is invalid.');
                    }

                    return new MenuRouteRegistrar($list, $listPublic, $create, $update, $delete);
                },
            );
    }
}
