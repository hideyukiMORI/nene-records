<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use NeNeRecords\Setting\UpdateSettingInput;
use NeNeRecords\Setting\UpdateSettingUseCaseInterface;

/**
 * Activate a theme = set the org's `active_theme` setting, but only after
 * verifying the key resolves to a real theme (a built-in id or a runtime theme
 * for this org). The raw setting write has no such check, so a typo / deleted
 * theme would silently fall back to the default — exactly the footgun an
 * automated author (ClaudeDesign) must not hit.
 */
final readonly class ActivateThemeUseCase implements ActivateThemeUseCaseInterface
{
    private const ACTIVE_THEME_SETTING = 'active_theme';

    public function __construct(
        private ThemeRepositoryInterface $themes,
        private UpdateSettingUseCaseInterface $settings,
    ) {
    }

    public function execute(ActivateThemeInput $input): ActivateThemeOutput
    {
        $key = $input->themeKey;

        if (!$this->themes->existsByKey($key) && !ThemeManifestValidator::isBuiltin($key)) {
            throw new ThemeNotFoundException($key);
        }

        $this->settings->execute(new UpdateSettingInput(
            settingKey: self::ACTIVE_THEME_SETTING,
            value: $key,
        ));

        return new ActivateThemeOutput(activeTheme: $key);
    }
}
