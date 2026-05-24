<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class SettingServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                SettingRepositoryInterface::class,
                static function (ContainerInterface $container): SettingRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoSettingRepository($query);
                },
            )
            ->set(
                ListSettingsUseCaseInterface::class,
                static function (ContainerInterface $container): ListSettingsUseCaseInterface {
                    $settings = $container->get(SettingRepositoryInterface::class);

                    if (!$settings instanceof SettingRepositoryInterface) {
                        throw new LogicException('Setting repository service is invalid.');
                    }

                    return new ListSettingsUseCase($settings);
                },
            )
            ->set(
                ListPublicSettingsUseCaseInterface::class,
                static function (ContainerInterface $container): ListPublicSettingsUseCaseInterface {
                    $settings = $container->get(SettingRepositoryInterface::class);

                    if (!$settings instanceof SettingRepositoryInterface) {
                        throw new LogicException('Setting repository service is invalid.');
                    }

                    return new ListPublicSettingsUseCase($settings);
                },
            )
            ->set(
                UpdateSettingUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateSettingUseCaseInterface {
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new UpdateSettingUseCase($transactions);
                },
            )
            ->set(
                ListSettingRevisionsUseCaseInterface::class,
                static function (ContainerInterface $container): ListSettingRevisionsUseCaseInterface {
                    $settings = $container->get(SettingRepositoryInterface::class);

                    if (!$settings instanceof SettingRepositoryInterface) {
                        throw new LogicException('Setting repository service is invalid.');
                    }

                    return new ListSettingRevisionsUseCase($settings);
                },
            )
            ->set(
                ListSettingsHandler::class,
                static function (ContainerInterface $container): ListSettingsHandler {
                    $useCase = $container->get(ListSettingsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListSettingsUseCaseInterface) {
                        throw new LogicException('ListSettings use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListSettingsHandler($useCase, $response);
                },
            )
            ->set(
                ListPublicSettingsHandler::class,
                static function (ContainerInterface $container): ListPublicSettingsHandler {
                    $useCase = $container->get(ListPublicSettingsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListPublicSettingsUseCaseInterface) {
                        throw new LogicException('ListPublicSettings use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListPublicSettingsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateSettingHandler::class,
                static function (ContainerInterface $container): UpdateSettingHandler {
                    $useCase = $container->get(UpdateSettingUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateSettingUseCaseInterface) {
                        throw new LogicException('UpdateSetting use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateSettingHandler($useCase, $response);
                },
            )
            ->set(
                ListSettingRevisionsHandler::class,
                static function (ContainerInterface $container): ListSettingRevisionsHandler {
                    $useCase = $container->get(ListSettingRevisionsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListSettingRevisionsUseCaseInterface) {
                        throw new LogicException('ListSettingRevisions use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListSettingRevisionsHandler($useCase, $response);
                },
            )
            ->set(
                SettingKeyNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): SettingKeyNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new SettingKeyNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                SettingValueInvalidExceptionHandler::class,
                static function (ContainerInterface $container): SettingValueInvalidExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new SettingValueInvalidExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.setting',
                static function (ContainerInterface $container): SettingRouteRegistrar {
                    $list = $container->get(ListSettingsHandler::class);
                    $listPublic = $container->get(ListPublicSettingsHandler::class);
                    $update = $container->get(UpdateSettingHandler::class);
                    $listRevisions = $container->get(ListSettingRevisionsHandler::class);

                    if (!$list instanceof ListSettingsHandler) {
                        throw new LogicException('ListSettings handler service is invalid.');
                    }

                    if (!$listPublic instanceof ListPublicSettingsHandler) {
                        throw new LogicException('ListPublicSettings handler service is invalid.');
                    }

                    if (!$update instanceof UpdateSettingHandler) {
                        throw new LogicException('UpdateSetting handler service is invalid.');
                    }

                    if (!$listRevisions instanceof ListSettingRevisionsHandler) {
                        throw new LogicException('ListSettingRevisions handler service is invalid.');
                    }

                    return new SettingRouteRegistrar($list, $listPublic, $update, $listRevisions);
                },
            );
    }
}
