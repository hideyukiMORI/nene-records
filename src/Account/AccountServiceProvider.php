<?php

declare(strict_types=1);

namespace NeNeRecords\Account;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Entitlement\EntitlementResolverInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class AccountServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                GetAccountUseCaseInterface::class,
                static function (ContainerInterface $container): GetAccountUseCaseInterface {
                    $organizations = $container->get(OrganizationRepositoryInterface::class);
                    $entitlements = $container->get(EntitlementResolverInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$entitlements instanceof EntitlementResolverInterface) {
                        throw new LogicException('Entitlement resolver service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new GetAccountUseCase($organizations, $entitlements, $entities);
                },
            )
            ->set(
                GetAccountHandler::class,
                static function (ContainerInterface $container): GetAccountHandler {
                    $useCase = $container->get(GetAccountUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetAccountUseCaseInterface) {
                        throw new LogicException('GetAccount use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetAccountHandler($useCase, $response);
                },
            )
            ->set(
                'nene-records.route_registrar.account',
                static function (ContainerInterface $container): AccountRouteRegistrar {
                    $handler = $container->get(GetAccountHandler::class);

                    if (!$handler instanceof GetAccountHandler) {
                        throw new LogicException('GetAccount handler service is invalid.');
                    }

                    return new AccountRouteRegistrar($handler);
                },
            );
    }
}
