<?php

declare(strict_types=1);

namespace NeNeRecords\Extension;

use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Routing\Router;
use Psr\Container\ContainerInterface;

/**
 * An optional, deploy-time module that extends the core without forking it.
 *
 * A module is a {@see ServiceProviderInterface} (registers its own DI services)
 * that ALSO contributes route registrars and domain exception handlers. Core
 * domains are wired directly in {@see \NeNeRecords\ApplicationServiceProvider};
 * optional/private modules (e.g. a commercial billing package) are discovered by
 * {@see ModuleRegistry} and composed on top.
 *
 * This is the typed, deploy-time extension mechanism chosen in ADR 0005 — NOT a
 * WordPress-style runtime plugin system. Modules are trusted code shipped with
 * the build (first-party or vetted), never untrusted user-uploaded code.
 *
 * Implementations must be constructible with no arguments (they are instantiated
 * by the registry); all dependencies are pulled from the container at use time.
 */
interface ModuleInterface extends ServiceProviderInterface
{
    /**
     * Route registrars this module contributes, appended after the core routes.
     *
     * @return list<callable(Router): void>
     */
    public function routeRegistrars(ContainerInterface $container): array;

    /**
     * Domain exception handlers this module contributes, appended after core.
     *
     * @return list<DomainExceptionHandlerInterface>
     */
    public function exceptionHandlers(ContainerInterface $container): array;
}
