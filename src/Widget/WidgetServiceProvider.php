<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class WidgetServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                WidgetRepositoryInterface::class,
                static function (ContainerInterface $container): WidgetRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoWidgetRepository($query, $orgId);
                },
            )
            ->set(
                ListWidgetsUseCaseInterface::class,
                static function (ContainerInterface $container): ListWidgetsUseCaseInterface {
                    $repo = $container->get(WidgetRepositoryInterface::class);
                    if (!$repo instanceof WidgetRepositoryInterface) {
                        throw new LogicException('Widget repository service is invalid.');
                    }

                    return new ListWidgetsUseCase($repo);
                },
            )
            ->set(
                CreateWidgetUseCaseInterface::class,
                static function (ContainerInterface $container): CreateWidgetUseCaseInterface {
                    $repo = $container->get(WidgetRepositoryInterface::class);
                    if (!$repo instanceof WidgetRepositoryInterface) {
                        throw new LogicException('Widget repository service is invalid.');
                    }

                    return new CreateWidgetUseCase($repo);
                },
            )
            ->set(
                UpdateWidgetUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateWidgetUseCaseInterface {
                    $repo = $container->get(WidgetRepositoryInterface::class);
                    if (!$repo instanceof WidgetRepositoryInterface) {
                        throw new LogicException('Widget repository service is invalid.');
                    }

                    return new UpdateWidgetUseCase($repo);
                },
            )
            ->set(
                DeleteWidgetUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteWidgetUseCaseInterface {
                    $repo = $container->get(WidgetRepositoryInterface::class);
                    if (!$repo instanceof WidgetRepositoryInterface) {
                        throw new LogicException('Widget repository service is invalid.');
                    }

                    return new DeleteWidgetUseCase($repo);
                },
            )
            ->set(
                ListWidgetsHandler::class,
                static function (ContainerInterface $container): ListWidgetsHandler {
                    $useCase = $container->get(ListWidgetsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof ListWidgetsUseCaseInterface) {
                        throw new LogicException('ListWidgets use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListWidgetsHandler($useCase, $response);
                },
            )
            ->set(
                CreateWidgetHandler::class,
                static function (ContainerInterface $container): CreateWidgetHandler {
                    $useCase = $container->get(CreateWidgetUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof CreateWidgetUseCaseInterface) {
                        throw new LogicException('CreateWidget use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateWidgetHandler($useCase, $response);
                },
            )
            ->set(
                UpdateWidgetHandler::class,
                static function (ContainerInterface $container): UpdateWidgetHandler {
                    $useCase = $container->get(UpdateWidgetUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof UpdateWidgetUseCaseInterface) {
                        throw new LogicException('UpdateWidget use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateWidgetHandler($useCase, $response);
                },
            )
            ->set(
                DeleteWidgetHandler::class,
                static function (ContainerInterface $container): DeleteWidgetHandler {
                    $useCase = $container->get(DeleteWidgetUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof DeleteWidgetUseCaseInterface) {
                        throw new LogicException('DeleteWidget use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteWidgetHandler($useCase, $response);
                },
            )
            ->set(
                WidgetNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): WidgetNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new WidgetNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                ListPublicWidgetsHandler::class,
                static function (ContainerInterface $container): ListPublicWidgetsHandler {
                    $useCase = $container->get(ListWidgetsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    if (!$useCase instanceof ListWidgetsUseCaseInterface) {
                        throw new LogicException('ListWidgets use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }
                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new ListPublicWidgetsHandler($useCase, $response, $responseFactory);
                },
            )
            ->set(
                'nene-records.route_registrar.widget',
                static function (ContainerInterface $container): WidgetRouteRegistrar {
                    $list = $container->get(ListWidgetsHandler::class);
                    $listPublic = $container->get(ListPublicWidgetsHandler::class);
                    $create = $container->get(CreateWidgetHandler::class);
                    $update = $container->get(UpdateWidgetHandler::class);
                    $delete = $container->get(DeleteWidgetHandler::class);

                    if (!$list instanceof ListWidgetsHandler) {
                        throw new LogicException('ListWidgets handler service is invalid.');
                    }
                    if (!$listPublic instanceof ListPublicWidgetsHandler) {
                        throw new LogicException('ListPublicWidgets handler service is invalid.');
                    }
                    if (!$create instanceof CreateWidgetHandler) {
                        throw new LogicException('CreateWidget handler service is invalid.');
                    }
                    if (!$update instanceof UpdateWidgetHandler) {
                        throw new LogicException('UpdateWidget handler service is invalid.');
                    }
                    if (!$delete instanceof DeleteWidgetHandler) {
                        throw new LogicException('DeleteWidget handler service is invalid.');
                    }

                    return new WidgetRouteRegistrar($list, $listPublic, $create, $update, $delete);
                },
            );
    }
}
