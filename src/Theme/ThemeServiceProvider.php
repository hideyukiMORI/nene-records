<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Psr\Container\ContainerInterface;

final readonly class ThemeServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ThemeRepositoryInterface::class,
                static function (ContainerInterface $container): ThemeRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoThemeRepository($query, $orgId);
                },
            )
            ->set(
                ListThemesUseCaseInterface::class,
                static function (ContainerInterface $container): ListThemesUseCaseInterface {
                    $repo = $container->get(ThemeRepositoryInterface::class);
                    if (!$repo instanceof ThemeRepositoryInterface) {
                        throw new LogicException('Theme repository service is invalid.');
                    }

                    return new ListThemesUseCase($repo);
                },
            )
            ->set(
                CreateThemeUseCaseInterface::class,
                static function (ContainerInterface $container): CreateThemeUseCaseInterface {
                    $repo = $container->get(ThemeRepositoryInterface::class);
                    if (!$repo instanceof ThemeRepositoryInterface) {
                        throw new LogicException('Theme repository service is invalid.');
                    }

                    return new CreateThemeUseCase($repo);
                },
            )
            ->set(
                UpdateThemeUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateThemeUseCaseInterface {
                    $repo = $container->get(ThemeRepositoryInterface::class);
                    if (!$repo instanceof ThemeRepositoryInterface) {
                        throw new LogicException('Theme repository service is invalid.');
                    }

                    return new UpdateThemeUseCase($repo);
                },
            )
            ->set(
                DeleteThemeUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteThemeUseCaseInterface {
                    $repo = $container->get(ThemeRepositoryInterface::class);
                    if (!$repo instanceof ThemeRepositoryInterface) {
                        throw new LogicException('Theme repository service is invalid.');
                    }

                    return new DeleteThemeUseCase($repo);
                },
            )
            ->set(
                ListThemesHandler::class,
                static function (ContainerInterface $container): ListThemesHandler {
                    $useCase = $container->get(ListThemesUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof ListThemesUseCaseInterface) {
                        throw new LogicException('ListThemes use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListThemesHandler($useCase, $response);
                },
            )
            ->set(
                GetThemeHandler::class,
                static function (ContainerInterface $container): GetThemeHandler {
                    $repo = $container->get(ThemeRepositoryInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$repo instanceof ThemeRepositoryInterface) {
                        throw new LogicException('Theme repository service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetThemeHandler($repo, $response);
                },
            )
            ->set(
                CreateThemeHandler::class,
                static function (ContainerInterface $container): CreateThemeHandler {
                    $useCase = $container->get(CreateThemeUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof CreateThemeUseCaseInterface) {
                        throw new LogicException('CreateTheme use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateThemeHandler($useCase, $response);
                },
            )
            ->set(
                UpdateThemeHandler::class,
                static function (ContainerInterface $container): UpdateThemeHandler {
                    $useCase = $container->get(UpdateThemeUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof UpdateThemeUseCaseInterface) {
                        throw new LogicException('UpdateTheme use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateThemeHandler($useCase, $response);
                },
            )
            ->set(
                DeleteThemeHandler::class,
                static function (ContainerInterface $container): DeleteThemeHandler {
                    $useCase = $container->get(DeleteThemeUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof DeleteThemeUseCaseInterface) {
                        throw new LogicException('DeleteTheme use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteThemeHandler($useCase, $response);
                },
            )
            ->set(
                ThemeNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): ThemeNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new ThemeNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.theme',
                static function (ContainerInterface $container): ThemeRouteRegistrar {
                    $list = $container->get(ListThemesHandler::class);
                    $get = $container->get(GetThemeHandler::class);
                    $create = $container->get(CreateThemeHandler::class);
                    $update = $container->get(UpdateThemeHandler::class);
                    $delete = $container->get(DeleteThemeHandler::class);

                    if (!$list instanceof ListThemesHandler) {
                        throw new LogicException('ListThemes handler service is invalid.');
                    }
                    if (!$get instanceof GetThemeHandler) {
                        throw new LogicException('GetTheme handler service is invalid.');
                    }
                    if (!$create instanceof CreateThemeHandler) {
                        throw new LogicException('CreateTheme handler service is invalid.');
                    }
                    if (!$update instanceof UpdateThemeHandler) {
                        throw new LogicException('UpdateTheme handler service is invalid.');
                    }
                    if (!$delete instanceof DeleteThemeHandler) {
                        throw new LogicException('DeleteTheme handler service is invalid.');
                    }

                    return new ThemeRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
