<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Extension;

use NeNeRecords\Extension\ModuleRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ModuleRegistryTest extends TestCase
{
    public function testDefaultDiscoversNoModulesInOssBuild(): void
    {
        // The commercial package is not installed in the core/OSS repo, so a fresh
        // build composes nothing on top of core.
        self::assertSame([], (new ModuleRegistry())->modules());
    }

    public function testDiscoversAnInstalledCandidate(): void
    {
        $modules = (new ModuleRegistry([FakeModule::class]))->modules();

        self::assertCount(1, $modules);
        foreach ($modules as $module) {
            self::assertInstanceOf(FakeModule::class, $module);
        }
    }

    public function testSkipsAMissingClass(): void
    {
        self::assertSame([], (new ModuleRegistry(['NeNeRecords\\Nope\\DoesNotExist']))->modules());
    }

    public function testSkipsAClassThatIsNotAModule(): void
    {
        // stdClass exists but does not implement ModuleInterface.
        self::assertSame([], (new ModuleRegistry([\stdClass::class]))->modules());
    }

    public function testModuleContributesRoutesAndHandlers(): void
    {
        $module = new FakeModule();
        $container = $this->createStub(ContainerInterface::class);

        self::assertCount(1, $module->routeRegistrars($container));
        self::assertSame([], $module->exceptionHandlers($container));
    }
}
