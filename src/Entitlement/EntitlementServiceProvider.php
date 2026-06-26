<?php

declare(strict_types=1);

namespace NeNeRecords\Entitlement;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class EntitlementServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            // Default: unlimited (self-host / no billing). Swap for a plan-based
            // resolver to opt into commercial limits on the hosted SaaS.
            ->set(
                EntitlementResolverInterface::class,
                static fn (ContainerInterface $container): EntitlementResolverInterface => new UnlimitedEntitlementResolver(),
            )
            ->set(
                FeatureNotEntitledExceptionHandler::class,
                static function (ContainerInterface $container): FeatureNotEntitledExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new FeatureNotEntitledExceptionHandler($problemDetails);
                },
            );
    }
}
