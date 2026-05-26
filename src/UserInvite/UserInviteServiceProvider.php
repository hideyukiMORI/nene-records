<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Mail\SymfonyMailer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class UserInviteServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                MailerInterface::class,
                static function (ContainerInterface $c): MailerInterface {
                    /** @var array<string, string> $env */
                    $env = $_ENV;
                    $dsn = $env['MAIL_DSN'] ?? 'smtp://mailpit:1025';
                    $fromAddress = $env['MAIL_FROM_ADDRESS'] ?? 'noreply@nene-records.local';
                    $fromName = $env['MAIL_FROM_NAME'] ?? 'NeNe Records';

                    return new SymfonyMailer($dsn, $fromAddress, $fromName);
                },
            )
            ->set(
                InviteUserUseCaseInterface::class,
                static function (ContainerInterface $c): InviteUserUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);
                    $mailer = $c->get(MailerInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    if (!$mailer instanceof MailerInterface) {
                        throw new LogicException('MailerInterface service is invalid.');
                    }

                    return new InviteUserUseCase($users, $mailer);
                },
            )
            ->set(
                AcceptInviteUseCaseInterface::class,
                static function (ContainerInterface $c): AcceptInviteUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new AcceptInviteUseCase($users);
                },
            )
            ->set(
                RequestPasswordResetUseCaseInterface::class,
                static function (ContainerInterface $c): RequestPasswordResetUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);
                    $mailer = $c->get(MailerInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    if (!$mailer instanceof MailerInterface) {
                        throw new LogicException('MailerInterface service is invalid.');
                    }

                    return new RequestPasswordResetUseCase($users, $mailer);
                },
            )
            ->set(
                ConfirmPasswordResetUseCaseInterface::class,
                static function (ContainerInterface $c): ConfirmPasswordResetUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface service is invalid.');
                    }

                    return new ConfirmPasswordResetUseCase($users);
                },
            )
            ->set(
                InviteUserHandler::class,
                static function (ContainerInterface $c): InviteUserHandler {
                    $useCase = $c->get(InviteUserUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof InviteUserUseCaseInterface) {
                        throw new LogicException('InviteUserUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new InviteUserHandler($useCase, $response);
                },
            )
            ->set(
                AcceptInviteHandler::class,
                static function (ContainerInterface $c): AcceptInviteHandler {
                    $useCase = $c->get(AcceptInviteUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof AcceptInviteUseCaseInterface) {
                        throw new LogicException('AcceptInviteUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new AcceptInviteHandler($useCase, $responseFactory);
                },
            )
            ->set(
                RequestPasswordResetHandler::class,
                static function (ContainerInterface $c): RequestPasswordResetHandler {
                    $useCase = $c->get(RequestPasswordResetUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof RequestPasswordResetUseCaseInterface) {
                        throw new LogicException('RequestPasswordResetUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new RequestPasswordResetHandler($useCase, $responseFactory);
                },
            )
            ->set(
                ConfirmPasswordResetHandler::class,
                static function (ContainerInterface $c): ConfirmPasswordResetHandler {
                    $useCase = $c->get(ConfirmPasswordResetUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof ConfirmPasswordResetUseCaseInterface) {
                        throw new LogicException('ConfirmPasswordResetUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface service is invalid.');
                    }

                    return new ConfirmPasswordResetHandler($useCase, $responseFactory);
                },
            )
            ->set(
                InvalidInviteTokenExceptionHandler::class,
                static function (ContainerInterface $c): InvalidInviteTokenExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new InvalidInviteTokenExceptionHandler($problemDetails);
                },
            )
            ->set(
                InvalidPasswordResetTokenExceptionHandler::class,
                static function (ContainerInterface $c): InvalidPasswordResetTokenExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new InvalidPasswordResetTokenExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.user_invite',
                static function (ContainerInterface $c): UserInviteRouteRegistrar {
                    $invite = $c->get(InviteUserHandler::class);
                    $accept = $c->get(AcceptInviteHandler::class);
                    $requestReset = $c->get(RequestPasswordResetHandler::class);
                    $confirmReset = $c->get(ConfirmPasswordResetHandler::class);

                    if (!$invite instanceof InviteUserHandler) {
                        throw new LogicException('InviteUserHandler service is invalid.');
                    }

                    if (!$accept instanceof AcceptInviteHandler) {
                        throw new LogicException('AcceptInviteHandler service is invalid.');
                    }

                    if (!$requestReset instanceof RequestPasswordResetHandler) {
                        throw new LogicException('RequestPasswordResetHandler service is invalid.');
                    }

                    if (!$confirmReset instanceof ConfirmPasswordResetHandler) {
                        throw new LogicException('ConfirmPasswordResetHandler service is invalid.');
                    }

                    return new UserInviteRouteRegistrar($invite, $accept, $requestReset, $confirmReset);
                },
            );
    }
}
