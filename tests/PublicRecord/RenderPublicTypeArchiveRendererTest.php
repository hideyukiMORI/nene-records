<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use Nene2\Config\AppConfig;
use Nene2\Config\AppEnvironment;
use Nene2\Config\DatabaseConfig;
use Nene2\View\HtmlResponseFactory;
use Nene2\View\NativePhpViewRenderer;
use NeNeRecords\Media\Media;
use NeNeRecords\PublicRecord\FrontPageSetting;
use NeNeRecords\PublicRecord\GetPublicTypeArchiveOutput;
use NeNeRecords\PublicRecord\RenderPublicTypeArchiveRenderer;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * A type archive has no image field of its own, so it exercises the
 * `default_og_image` fallback (#912) directly: og:image appears only when the
 * setting is configured.
 */
final class RenderPublicTypeArchiveRendererTest extends TestCase
{
    /** @param list<SettingDef> $settingDefs */
    private function renderArchive(array $settingDefs): string
    {
        $factory = new Psr17Factory();
        $media = new InMemoryMediaRepository([
            new Media(
                id: 7,
                originalName: 'card.png',
                storedName: 'card.png',
                mimeType: 'image/png',
                size: 1000,
                url: '/media/2026/06/card.png',
                createdAt: '2026-06-01 00:00:00',
            ),
        ]);

        $frontPage = new FrontPageSetting(
            new InMemorySettingRepository([new SettingDef('front_page', 'text', '', true, 'Front page')]),
            new InMemoryEntityRepository(),
            new InMemoryEntityTypeRepository(),
        );
        $publicSettings = new ListPublicSettingsUseCase(
            new InMemorySettingRepository($settingDefs),
            $media,
            $frontPage,
        );

        $renderer = new NativePhpViewRenderer(dirname(__DIR__, 2) . '/templates');
        $html = new HtmlResponseFactory($factory, $factory, $renderer);
        $config = new AppConfig(
            environment: AppEnvironment::Test,
            debug: true,
            name: 'NeNe Records',
            database: new DatabaseConfig(
                url: null,
                environment: 'test',
                adapter: 'sqlite',
                host: '',
                port: 1,
                name: ':memory:',
                user: '',
                password: '',
                charset: '',
            ),
            machineApiKey: null,
        );

        $archiveRenderer = new RenderPublicTypeArchiveRenderer(
            $publicSettings,
            $html,
            $config,
            dirname(__DIR__, 2),
        );

        $archive = new GetPublicTypeArchiveOutput('article', 'Articles', [], 0, 0, 20);
        $request = $factory->createServerRequest('GET', 'https://example.test/article');

        return (string) $archiveRenderer->render($archive, $request)->getBody();
    }

    public function testEmitsDefaultOgImageWhenSet(): void
    {
        $html = $this->renderArchive([
            new SettingDef('site_name', 'text', 'NeNe Records', true, 'Site name'),
            new SettingDef('default_og_image', 'media', '7', true, 'Default social image (og:image)'),
        ]);

        self::assertStringContainsString('property="og:image" content="https://example.test/media/og/2026/06/card.png"', $html);
        self::assertStringContainsString('name="twitter:image" content="https://example.test/media/og/2026/06/card.png"', $html);
        self::assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
    }

    public function testOmitsOgImageWhenUnset(): void
    {
        $html = $this->renderArchive([
            new SettingDef('site_name', 'text', 'NeNe Records', true, 'Site name'),
        ]);

        self::assertStringNotContainsString('property="og:image"', $html);
        self::assertStringContainsString('name="twitter:card" content="summary"', $html);
    }

    public function testDeclaresFaviconInHead(): void
    {
        // The archive shell must declare a favicon so Google/browsers use the real icon
        // instead of an auto-generated letter monogram (#986). Base-relative paths.
        $html = $this->renderArchive([
            new SettingDef('site_name', 'text', 'NeNe Records', true, 'Site name'),
        ]);

        self::assertStringContainsString('<link rel="icon" href="assets/favicon/favicon.svg" type="image/svg+xml" />', $html);
        self::assertStringContainsString('<link rel="manifest" href="assets/favicon/site.webmanifest" />', $html);
    }
}
