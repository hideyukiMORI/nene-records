<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Extension;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\Routing\Router;
use NeNeRecords\Extension\ModuleInterface;
use Psr\Container\ContainerInterface;

/**
 * Minimal {@see ModuleInterface} test double: contributes one route registrar and
 * no exception handlers, so registry/composition behaviour can be asserted without
 * a real private package.
 */
final class FakeModule implements ModuleInterface
{
    public function register(ContainerBuilder $builder): void
    {
    }

    public function routeRegistrars(ContainerInterface $container): array
    {
        return [static function (Router $router): void {
        }];
    }

    public function exceptionHandlers(ContainerInterface $container): array
    {
        return [];
    }
}
