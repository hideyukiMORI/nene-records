<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Config\ConfigCipherInterface;
use Psr\Container\ContainerInterface;

final readonly class OrgConnectServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ConnectTokenRepositoryInterface::class,
                static function (ContainerInterface $container): ConnectTokenRepositoryInterface {
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

                    return new PdoConnectTokenRepository($query, $orgId, $clock);
                },
            )
            ->set(
                ConnectTokenProviderInterface::class,
                static function (ContainerInterface $container): ConnectTokenProviderInterface {
                    $tokens = $container->get(ConnectTokenRepositoryInterface::class);
                    $cipher = $container->get(ConfigCipherInterface::class);

                    if (!$tokens instanceof ConnectTokenRepositoryInterface) {
                        throw new LogicException('Connect token repository service is invalid.');
                    }

                    if (!$cipher instanceof ConfigCipherInterface) {
                        throw new LogicException('Config cipher service is invalid.');
                    }

                    return new StoredConnectTokenProvider($tokens, $cipher);
                },
            )
            ->set(
                ListConnectTokensUseCaseInterface::class,
                static function (ContainerInterface $container): ListConnectTokensUseCaseInterface {
                    $tokens = $container->get(ConnectTokenRepositoryInterface::class);

                    if (!$tokens instanceof ConnectTokenRepositoryInterface) {
                        throw new LogicException('Connect token repository service is invalid.');
                    }

                    return new ListConnectTokensUseCase($tokens);
                },
            )
            ->set(
                SaveConnectTokenUseCaseInterface::class,
                static function (ContainerInterface $container): SaveConnectTokenUseCaseInterface {
                    $tokens = $container->get(ConnectTokenRepositoryInterface::class);
                    $cipher = $container->get(ConfigCipherInterface::class);

                    if (!$tokens instanceof ConnectTokenRepositoryInterface) {
                        throw new LogicException('Connect token repository service is invalid.');
                    }

                    if (!$cipher instanceof ConfigCipherInterface) {
                        throw new LogicException('Config cipher service is invalid.');
                    }

                    return new SaveConnectTokenUseCase($tokens, $cipher);
                },
            )
            ->set(
                DeleteConnectTokenUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteConnectTokenUseCaseInterface {
                    $tokens = $container->get(ConnectTokenRepositoryInterface::class);

                    if (!$tokens instanceof ConnectTokenRepositoryInterface) {
                        throw new LogicException('Connect token repository service is invalid.');
                    }

                    return new DeleteConnectTokenUseCase($tokens);
                },
            )
            ->set(
                ListConnectTokensHandler::class,
                static function (ContainerInterface $container): ListConnectTokensHandler {
                    $useCase = $container->get(ListConnectTokensUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListConnectTokensUseCaseInterface) {
                        throw new LogicException('ListConnectTokens use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListConnectTokensHandler($useCase, $response);
                },
            )
            ->set(
                SaveConnectTokenHandler::class,
                static function (ContainerInterface $container): SaveConnectTokenHandler {
                    $useCase = $container->get(SaveConnectTokenUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof SaveConnectTokenUseCaseInterface) {
                        throw new LogicException('SaveConnectToken use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new SaveConnectTokenHandler($useCase, $response);
                },
            )
            ->set(
                DeleteConnectTokenHandler::class,
                static function (ContainerInterface $container): DeleteConnectTokenHandler {
                    $useCase = $container->get(DeleteConnectTokenUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof DeleteConnectTokenUseCaseInterface) {
                        throw new LogicException('DeleteConnectToken use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteConnectTokenHandler($useCase, $response);
                },
            )
            ->set(
                ConnectTokenNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): ConnectTokenNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new ConnectTokenNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.connect_token',
                static function (ContainerInterface $container): ConnectTokenRouteRegistrar {
                    $list = $container->get(ListConnectTokensHandler::class);
                    $save = $container->get(SaveConnectTokenHandler::class);
                    $delete = $container->get(DeleteConnectTokenHandler::class);

                    if (!$list instanceof ListConnectTokensHandler) {
                        throw new LogicException('ListConnectTokens handler service is invalid.');
                    }

                    if (!$save instanceof SaveConnectTokenHandler) {
                        throw new LogicException('SaveConnectToken handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteConnectTokenHandler) {
                        throw new LogicException('DeleteConnectToken handler service is invalid.');
                    }

                    return new ConnectTokenRouteRegistrar($list, $save, $delete);
                },
            );
    }
}
