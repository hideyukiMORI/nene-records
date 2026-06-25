<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\ApplicationServiceProvider;
use NeNeRecords\Auth\LoginUseCase;
use NeNeRecords\Organization\CreateOrganizationUseCaseInterface;
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

                    /** @var RequestScopedHolder<int> $orgHolder */
                    return new PublicSignupUseCase($createOrg, $createUser, $login, $orgHolder);
                },
            )
            ->set(
                PublicSignupHandler::class,
                static function (ContainerInterface $c): PublicSignupHandler {
                    $useCase = $c->get(PublicSignupUseCase::class);
                    $json    = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof PublicSignupUseCase) {
                        throw new LogicException('PublicSignupUseCase is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory is invalid.');
                    }

                    return new PublicSignupHandler($useCase, $json);
                },
            )
            ->set(
                PublicSignupRouteRegistrar::class,
                static function (ContainerInterface $c): PublicSignupRouteRegistrar {
                    $handler = $c->get(PublicSignupHandler::class);

                    if (!$handler instanceof PublicSignupHandler) {
                        throw new LogicException('PublicSignupHandler is invalid.');
                    }

                    return new PublicSignupRouteRegistrar($handler);
                },
            );
    }
}
