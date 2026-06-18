<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use Nene2\Validation\ValidationException;
use NeNeRecords\Theme\CreateThemeInput;
use NeNeRecords\Theme\CreateThemeUseCase;
use NeNeRecords\Theme\DeleteThemeInput;
use NeNeRecords\Theme\DeleteThemeUseCase;
use NeNeRecords\Theme\ListThemesUseCase;
use NeNeRecords\Theme\ThemeNotFoundException;
use NeNeRecords\Theme\UpdateThemeInput;
use NeNeRecords\Theme\UpdateThemeUseCase;
use PHPUnit\Framework\TestCase;

final class ThemeUseCaseTest extends TestCase
{
    public function testCreatePersistsValidManifestAndListsIt(): void
    {
        $repo = new InMemoryThemeRepository();
        $create = new CreateThemeUseCase($repo);

        $output = $create->execute(new CreateThemeInput(ThemeManifestFixture::valid()));

        self::assertSame('midnight', $output->theme->themeKey);
        self::assertSame('Midnight', $output->theme->name);

        $list = (new ListThemesUseCase($repo))->execute();
        self::assertCount(1, $list->items);
    }

    public function testCreateRejectsInvalidManifest(): void
    {
        $repo = new InMemoryThemeRepository();
        $create = new CreateThemeUseCase($repo);

        $this->expectException(ValidationException::class);
        $create->execute(new CreateThemeInput(ThemeManifestFixture::valid(['version' => 'bad'])));
    }

    public function testCreateRejectsDuplicateKey(): void
    {
        $repo = new InMemoryThemeRepository();
        $create = new CreateThemeUseCase($repo);
        $create->execute(new CreateThemeInput(ThemeManifestFixture::valid()));

        $this->expectException(ValidationException::class);
        $create->execute(new CreateThemeInput(ThemeManifestFixture::valid()));
    }

    public function testUpdateChangesManifest(): void
    {
        $repo = new InMemoryThemeRepository();
        (new CreateThemeUseCase($repo))->execute(new CreateThemeInput(ThemeManifestFixture::valid()));

        $update = new UpdateThemeUseCase($repo);
        $output = $update->execute(new UpdateThemeInput(
            themeKey: 'midnight',
            manifest: ThemeManifestFixture::valid(['name' => 'Midnight 2', 'version' => '1.1.0']),
        ));

        self::assertSame('Midnight 2', $output->theme->name);
        self::assertSame('1.1.0', $output->theme->version);
    }

    public function testUpdateRejectsKeyMismatch(): void
    {
        $repo = new InMemoryThemeRepository();
        (new CreateThemeUseCase($repo))->execute(new CreateThemeInput(ThemeManifestFixture::valid()));

        $this->expectException(ValidationException::class);
        (new UpdateThemeUseCase($repo))->execute(new UpdateThemeInput(
            themeKey: 'midnight',
            manifest: ThemeManifestFixture::valid(['id' => 'other-key']),
        ));
    }

    public function testUpdateMissingThrowsNotFound(): void
    {
        $repo = new InMemoryThemeRepository();

        $this->expectException(ThemeNotFoundException::class);
        (new UpdateThemeUseCase($repo))->execute(new UpdateThemeInput(
            themeKey: 'midnight',
            manifest: ThemeManifestFixture::valid(),
        ));
    }

    public function testDeleteRemovesTheme(): void
    {
        $repo = new InMemoryThemeRepository();
        (new CreateThemeUseCase($repo))->execute(new CreateThemeInput(ThemeManifestFixture::valid()));

        (new DeleteThemeUseCase($repo))->execute(new DeleteThemeInput('midnight'));

        self::assertFalse($repo->existsByKey('midnight'));
    }

    public function testDeleteMissingThrowsNotFound(): void
    {
        $repo = new InMemoryThemeRepository();

        $this->expectException(ThemeNotFoundException::class);
        (new DeleteThemeUseCase($repo))->execute(new DeleteThemeInput('nope'));
    }
}
