<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Nene2\Middleware\RateLimitStorageInterface;
use NeNeRecords\ApplicationServiceProvider;
use NeNeRecords\Auth\LoginUseCase;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Organization\CreateOrganizationUseCaseInterface;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\User\CreateUserUseCaseInterface;
use Psr\Container\ContainerInterface;

final readonly class SignupServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                PublicSignupUseCase::class,
                static function (ContainerInterface $c): PublicSignupUseCase {
                    $createOrg  = $c->get(CreateOrganizationUseCaseInterface::class);
                    $createUser = $c->get(CreateUserUseCaseInterface::class);
                    $login      = $c->get(LoginUseCase::class);
                    $orgHolder  = $c->get(ApplicationServiceProvider::ORG_ID_HOLDER);
                    $users      = $c->get(UserRepositoryInterface::class);
                    $mailer     = $c->get(MailerInterface::class);
                    $clock      = $c->get(ClockInterface::class);

                    if (!$createOrg instanceof CreateOrganizationUseCaseInterface) {
                        throw new LogicException('CreateOrganizationUseCaseInterface is invalid.');
                    }

                    if (!$createUser instanceof CreateUserUseCaseInterface) {
                        throw new LogicException('CreateUserUseCaseInterface is invalid.');
                    }

                    if (!$login instanceof LoginUseCase) {
                        throw new LogicException('LoginUseCase is invalid.');
                    }

                    if (!$orgHolder instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface is invalid.');
                    }

                    if (!$mailer instanceof MailerInterface) {
                        throw new LogicException('MailerInterface is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface is invalid.');
                    }

                    /** @var RequestScopedHolder<int> $orgHolder */
                    return new PublicSignupUseCase($createOrg, $createUser, $login, $orgHolder, $users, $mailer, $clock);
                },
            )
            ->set(
                PublicSignupHandler::class,
                static function (ContainerInterface $c): PublicSignupHandler {
                    $useCase     = $c->get(PublicSignupUseCase::class);
                    $json        = $c->get(JsonResponseFactory::class);
                    $problems    = $c->get(ProblemDetailsResponseFactory::class);
                    $rateLimiter = $c->get(RateLimitStorageInterface::class);
                    $clock       = $c->get(ClockInterface::class);

                    if (!$useCase instanceof PublicSignupUseCase) {
                        throw new LogicException('PublicSignupUseCase is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    if (!$problems instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory is invalid.');
                    }

                    if (!$rateLimiter instanceof RateLimitStorageInterface) {
                        throw new LogicException('RateLimitStorageInterface is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface is invalid.');
                    }

                    return new PublicSignupHandler($useCase, $json, $problems, $rateLimiter, $clock);
                },
            )
            ->set(
                ConfirmEmailUseCase::class,
                static function (ContainerInterface $c): ConfirmEmailUseCase {
                    $users = $c->get(UserRepositoryInterface::class);
                    $orgs  = $c->get(OrganizationRepositoryInterface::class);
                    $clock = $c->get(ClockInterface::class);
                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepositoryInterface is invalid.');
                    }
                    if (!$orgs instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('OrganizationRepositoryInterface is invalid.');
                    }
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface is invalid.');
                    }

                    return new ConfirmEmailUseCase($users, $orgs, $clock);
                },
            )
            ->set(
                ConfirmEmailHandler::class,
                static function (ContainerInterface $c): ConfirmEmailHandler {
                    $useCase = $c->get(ConfirmEmailUseCase::class);
                    $json    = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof ConfirmEmailUseCase) {
                        throw new LogicException('ConfirmEmailUseCase is invalid.');
                    }
                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    return new ConfirmEmailHandler($useCase, $json);
                },
            )
            ->set(
                PublicSignupRouteRegistrar::class,
                static function (ContainerInterface $c): PublicSignupRouteRegistrar {
                    $handler = $c->get(PublicSignupHandler::class);
                    $confirm = $c->get(ConfirmEmailHandler::class);

                    if (!$handler instanceof PublicSignupHandler) {
                        throw new LogicException('PublicSignupHandler is invalid.');
                    }
                    if (!$confirm instanceof ConfirmEmailHandler) {
                        throw new LogicException('ConfirmEmailHandler is invalid.');
                    }

                    return new PublicSignupRouteRegistrar($handler, $confirm);
                },
            );
    }
}
