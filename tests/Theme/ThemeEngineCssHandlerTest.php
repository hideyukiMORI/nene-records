<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Theme\ThemeEngineCssHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class ThemeEngineCssHandlerTest extends TestCase
{
    public function testReturnsTheEngineCssIncludingFlagImplementations(): void
    {
        $factory = new Psr17Factory();
        $handler = new ThemeEngineCssHandler(new JsonResponseFactory($factory, $factory));
        $response = $handler->handle($this->createStub(ServerRequestInterface::class));

        $payload = json_decode((string) $response->getBody(), true);
        self::assertIsArray($payload);
        self::assertGreaterThan(0, $payload['bytes']);
        // The whole point is the deployed flag CSS — assert a known flag rule is present.
        self::assertStringContainsString("[data-cards='framed']", $payload['css']);
        self::assertStringContainsString("[data-media='duotone']", $payload['css']);
        self::assertSame($payload['bytes'], strlen($payload['css']));
    }
}
