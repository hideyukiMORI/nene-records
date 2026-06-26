<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use NeNeRecords\Setting\UpdateSettingInput;
use NeNeRecords\Setting\UpdateSettingOutput;
use NeNeRecords\Setting\UpdateSettingUseCaseInterface;
use NeNeRecords\Theme\ActivateThemeInput;
use NeNeRecords\Theme\ActivateThemeUseCase;
use NeNeRecords\Theme\ThemeNotFoundException;
use NeNeRecords\Theme\ThemeRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ActivateThemeUseCaseTest extends TestCase
{
    public function testActivatesAnExistingRuntimeTheme(): void
    {
        $themes = $this->createStub(ThemeRepositoryInterface::class);
        $themes->method('existsByKey')->willReturn(true);

        $settings = $this->createMock(UpdateSettingUseCaseInterface::class);
        $settings->expects($this->once())
            ->method('execute')
            ->with($this->callback(
                static fn (mixed $input): bool => $input instanceof UpdateSettingInput
                    && $input->settingKey === 'active_theme'
                    && $input->value === 'midnight',
            ))
            ->willReturn(new UpdateSettingOutput(settingKey: 'active_theme', value: 'midnight', updatedAt: ''));

        $output = (new ActivateThemeUseCase($themes, $settings))->execute(new ActivateThemeInput('midnight'));

        self::assertSame('midnight', $output->activeTheme);
    }

    public function testActivatesABuiltinThemeEvenWhenNotInTheRepository(): void
    {
        $themes = $this->createStub(ThemeRepositoryInterface::class);
        $themes->method('existsByKey')->willReturn(false); // not a runtime theme

        $settings = $this->createMock(UpdateSettingUseCaseInterface::class);
        $settings->expects($this->once())
            ->method('execute')
            ->willReturn(new UpdateSettingOutput(settingKey: 'active_theme', value: 'aurora', updatedAt: ''));

        // 'aurora' is a built-in id (ThemeManifestValidator::isBuiltin).
        $output = (new ActivateThemeUseCase($themes, $settings))->execute(new ActivateThemeInput('aurora'));

        self::assertSame('aurora', $output->activeTheme);
    }

    public function testRejectsAnUnknownThemeWithoutTouchingSettings(): void
    {
        $themes = $this->createStub(ThemeRepositoryInterface::class);
        $themes->method('existsByKey')->willReturn(false);

        $settings = $this->createMock(UpdateSettingUseCaseInterface::class);
        $settings->expects($this->never())->method('execute');

        $this->expectException(ThemeNotFoundException::class);
        (new ActivateThemeUseCase($themes, $settings))->execute(new ActivateThemeInput('no-such-theme-xyz'));
    }
}
