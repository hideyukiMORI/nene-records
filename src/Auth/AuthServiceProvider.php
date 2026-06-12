<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use LogicException;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                UserRepositoryInterface::class,
                static function (ContainerInterface $container): UserRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('DatabaseQueryExecutorInterface service is invalid.');
                    }

                    return new PdoUserRepository($query);
                },
            )
            ->set(
                LoginUseCase::class,
                static function (ContainerInterface $container): LoginUseCase {
                    $users = $container->get(UserRepositoryInterface::class);
                    $tokenIssuer = $container->get('nene-records.token_issuer');

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    if (!$tokenIssuer instanceof TokenIssuerInterface) {
                        throw new LogicException('TokenIssuerInterface service is invalid.');
                    }

                    /** @var TokenIssuerInterface $tokenIssuer */
                    return new LoginUseCase($users, $tokenIssuer);
                },
            )
            ->set(
                LoginHandler::class,
                static function (ContainerInterface $container): LoginHandler {
                    $useCase = $container->get(LoginUseCase::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof LoginUseCase) {
                        throw new LogicException('LoginUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new LoginHandler($useCase, $response);
                },
            )
            ->set(
                LogoutHandler::class,
                static function (ContainerInterface $container): LogoutHandler {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new LogoutHandler($responseFactory);
                },
            )
            ->set(
                InvalidCredentialsExceptionHandler::class,
                static function (ContainerInterface $container): InvalidCredentialsExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new InvalidCredentialsExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.auth',
                static function (ContainerInterface $container): AuthRouteRegistrar {
                    $loginHandler = $container->get(LoginHandler::class);
                    $logoutHandler = $container->get(LogoutHandler::class);

                    if (!$loginHandler instanceof LoginHandler) {
                        throw new LogicException('LoginHandler service is invalid.');
                    }

                    if (!$logoutHandler instanceof LogoutHandler) {
                        throw new LogicException('LogoutHandler service is invalid.');
                    }

                    return new AuthRouteRegistrar($loginHandler, $logoutHandler);
                },
            );
    }
}
