<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Auth\UserRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class UserServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ListUsersUseCaseInterface::class,
                static function (ContainerInterface $c): ListUsersUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new ListUsersUseCase($users);
                },
            )
            ->set(
                GetUserByIdUseCaseInterface::class,
                static function (ContainerInterface $c): GetUserByIdUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new GetUserByIdUseCase($users);
                },
            )
            ->set(
                CreateUserUseCaseInterface::class,
                static function (ContainerInterface $c): CreateUserUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new CreateUserUseCase($users);
                },
            )
            ->set(
                UpdateUserRoleUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateUserRoleUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new UpdateUserRoleUseCase($users);
                },
            )
            ->set(
                DeleteUserUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteUserUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new DeleteUserUseCase($users);
                },
            )
            ->set(
                ChangeOwnPasswordUseCaseInterface::class,
                static function (ContainerInterface $c): ChangeOwnPasswordUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new ChangeOwnPasswordUseCase($users);
                },
            )
            ->set(
                AdminResetPasswordUseCaseInterface::class,
                static function (ContainerInterface $c): AdminResetPasswordUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new AdminResetPasswordUseCase($users);
                },
            )
            ->set(
                ListUsersHandler::class,
                static function (ContainerInterface $c): ListUsersHandler {
                    $useCase = $c->get(ListUsersUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListUsersUseCaseInterface) {
                        throw new LogicException('ListUsersUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new ListUsersHandler($useCase, $response);
                },
            )
            ->set(
                GetUserByIdHandler::class,
                static function (ContainerInterface $c): GetUserByIdHandler {
                    $useCase = $c->get(GetUserByIdUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetUserByIdUseCaseInterface) {
                        throw new LogicException('GetUserByIdUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new GetUserByIdHandler($useCase, $response);
                },
            )
            ->set(
                CreateUserHandler::class,
                static function (ContainerInterface $c): CreateUserHandler {
                    $useCase = $c->get(CreateUserUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateUserUseCaseInterface) {
                        throw new LogicException('CreateUserUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new CreateUserHandler($useCase, $response);
                },
            )
            ->set(
                UpdateUserRoleHandler::class,
                static function (ContainerInterface $c): UpdateUserRoleHandler {
                    $useCase = $c->get(UpdateUserRoleUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateUserRoleUseCaseInterface) {
                        throw new LogicException('UpdateUserRoleUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new UpdateUserRoleHandler($useCase, $response);
                },
            )
            ->set(
                AdminResetPasswordHandler::class,
                static function (ContainerInterface $c): AdminResetPasswordHandler {
                    $useCase = $c->get(AdminResetPasswordUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof AdminResetPasswordUseCaseInterface) {
                        throw new LogicException('AdminResetPasswordUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new AdminResetPasswordHandler($useCase, $responseFactory);
                },
            )
            ->set(
                DeleteUserHandler::class,
                static function (ContainerInterface $c): DeleteUserHandler {
                    $useCase = $c->get(DeleteUserUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteUserUseCaseInterface) {
                        throw new LogicException('DeleteUserUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new DeleteUserHandler($useCase, $responseFactory);
                },
            )
            ->set(
                ChangeOwnPasswordHandler::class,
                static function (ContainerInterface $c): ChangeOwnPasswordHandler {
                    $useCase = $c->get(ChangeOwnPasswordUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof ChangeOwnPasswordUseCaseInterface) {
                        throw new LogicException('ChangeOwnPasswordUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new ChangeOwnPasswordHandler($useCase, $responseFactory);
                },
            )
            ->set(
                UserNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): UserNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new UserNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                UserEmailConflictExceptionHandler::class,
                static function (ContainerInterface $c): UserEmailConflictExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new UserEmailConflictExceptionHandler($problemDetails);
                },
            )
            ->set(
                CannotDeleteSelfExceptionHandler::class,
                static function (ContainerInterface $c): CannotDeleteSelfExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new CannotDeleteSelfExceptionHandler($problemDetails);
                },
            )
            ->set(
                InvalidUserRoleExceptionHandler::class,
                static function (ContainerInterface $c): InvalidUserRoleExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new InvalidUserRoleExceptionHandler($problemDetails);
                },
            )
            ->set(
                InvalidCurrentPasswordExceptionHandler::class,
                static function (ContainerInterface $c): InvalidCurrentPasswordExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new InvalidCurrentPasswordExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.user',
                static function (ContainerInterface $c): UserRouteRegistrar {
                    $list = $c->get(ListUsersHandler::class);
                    $get = $c->get(GetUserByIdHandler::class);
                    $create = $c->get(CreateUserHandler::class);
                    $updateRole = $c->get(UpdateUserRoleHandler::class);
                    $resetPassword = $c->get(AdminResetPasswordHandler::class);
                    $delete = $c->get(DeleteUserHandler::class);
                    $changeOwn = $c->get(ChangeOwnPasswordHandler::class);

                    if (!$list instanceof ListUsersHandler) {
                        throw new LogicException('ListUsersHandler service is invalid.');
                    }

                    if (!$get instanceof GetUserByIdHandler) {
                        throw new LogicException('GetUserByIdHandler service is invalid.');
                    }

                    if (!$create instanceof CreateUserHandler) {
                        throw new LogicException('CreateUserHandler service is invalid.');
                    }

                    if (!$updateRole instanceof UpdateUserRoleHandler) {
                        throw new LogicException('UpdateUserRoleHandler service is invalid.');
                    }

                    if (!$resetPassword instanceof AdminResetPasswordHandler) {
                        throw new LogicException('AdminResetPasswordHandler service is invalid.');
                    }

                    if (!$delete instanceof DeleteUserHandler) {
                        throw new LogicException('DeleteUserHandler service is invalid.');
                    }

                    if (!$changeOwn instanceof ChangeOwnPasswordHandler) {
                        throw new LogicException('ChangeOwnPasswordHandler service is invalid.');
                    }

                    return new UserRouteRegistrar($list, $get, $create, $updateRole, $resetPassword, $delete, $changeOwn);
                },
            );
    }
}
