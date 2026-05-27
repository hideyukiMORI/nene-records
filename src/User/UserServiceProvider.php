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
                ChangePasswordUseCaseInterface::class,
                static function (ContainerInterface $c): ChangePasswordUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new ChangePasswordUseCase($users);
                },
            )
            ->set(
                ChangeEmailUseCaseInterface::class,
                static function (ContainerInterface $c): ChangeEmailUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new ChangeEmailUseCase($users);
                },
            )
            ->set(
                ResetUserPasswordUseCaseInterface::class,
                static function (ContainerInterface $c): ResetUserPasswordUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new ResetUserPasswordUseCase($users);
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
                ResetUserPasswordHandler::class,
                static function (ContainerInterface $c): ResetUserPasswordHandler {
                    $useCase = $c->get(ResetUserPasswordUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof ResetUserPasswordUseCaseInterface) {
                        throw new LogicException('ResetUserPasswordUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new ResetUserPasswordHandler($useCase, $responseFactory);
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
                ChangePasswordHandler::class,
                static function (ContainerInterface $c): ChangePasswordHandler {
                    $useCase = $c->get(ChangePasswordUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof ChangePasswordUseCaseInterface) {
                        throw new LogicException('ChangePasswordUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new ChangePasswordHandler($useCase, $responseFactory);
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
                ChangeEmailHandler::class,
                static function (ContainerInterface $c): ChangeEmailHandler {
                    $useCase = $c->get(ChangeEmailUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof ChangeEmailUseCaseInterface) {
                        throw new LogicException('ChangeEmailUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new ChangeEmailHandler($useCase, $responseFactory);
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
                    $resetPassword = $c->get(ResetUserPasswordHandler::class);
                    $delete = $c->get(DeleteUserHandler::class);
                    $changePassword = $c->get(ChangePasswordHandler::class);
                    $changeEmail = $c->get(ChangeEmailHandler::class);

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

                    if (!$resetPassword instanceof ResetUserPasswordHandler) {
                        throw new LogicException('ResetUserPasswordHandler service is invalid.');
                    }

                    if (!$delete instanceof DeleteUserHandler) {
                        throw new LogicException('DeleteUserHandler service is invalid.');
                    }

                    if (!$changePassword instanceof ChangePasswordHandler) {
                        throw new LogicException('ChangePasswordHandler service is invalid.');
                    }

                    if (!$changeEmail instanceof ChangeEmailHandler) {
                        throw new LogicException('ChangeEmailHandler service is invalid.');
                    }

                    return new UserRouteRegistrar($list, $get, $create, $updateRole, $resetPassword, $delete, $changePassword, $changeEmail);
                },
            );
    }
}
