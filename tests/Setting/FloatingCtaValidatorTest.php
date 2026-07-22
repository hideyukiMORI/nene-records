<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use Nene2\Validation\ValidationException;
use NeNeRecords\Setting\FloatingCtaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FloatingCtaValidatorTest extends TestCase
{
    public function testDisabledDefaultPasses(): void
    {
        (new FloatingCtaValidator())->validate(
            '{"enabled":false,"position":"br","trigger":"always","content":{"label":""},"link":{"url":""}}',
        );
        $this->addToAssertionCount(1);
    }

    public function testEnabledValidConfigPasses(): void
    {
        (new FloatingCtaValidator())->validate((string) json_encode([
            'enabled' => true,
            'position' => 'bl',
            'trigger' => 'delay',
            'triggerValue' => 5,
            'accent' => '#D64525',
            'content' => ['icon' => '📅', 'iconId' => 'calendar', 'label' => '30分無料相談を予約', 'sub' => 'オンライン'],
            'link' => ['url' => 'https://calendar.app.google/x', 'newTab' => true],
            'conditions' => ['types' => ['page'], 'urlGlobs' => ['/services*'], 'exclude' => ['/admin*']],
            'bottomOffset' => 120,
            'dismissible' => true,
        ]));
        $this->addToAssertionCount(1);
    }

    public function testScrollTriggerWithPxPasses(): void
    {
        (new FloatingCtaValidator())->validate((string) json_encode([
            'enabled' => true,
            'trigger' => 'scroll',
            'triggerValue' => 600,
            'content' => ['label' => 'Book'],
            'link' => ['url' => 'https://x.test'],
        ]));
        $this->addToAssertionCount(1);
    }

    #[DataProvider('provideInvalid')]
    public function testInvalidConfigThrows(string $json): void
    {
        $this->expectException(ValidationException::class);
        (new FloatingCtaValidator())->validate($json);
    }

    /** @return iterable<string, array{string}> */
    public static function provideInvalid(): iterable
    {
        $base = ['enabled' => true, 'content' => ['label' => 'x'], 'link' => ['url' => 'https://x.test']];

        yield 'not an object' => ['[]'];
        yield 'invalid json' => ['{not json'];
        yield 'reserved position right-tab (P2)' => [(string) json_encode(['position' => 'right-tab'] + $base)];
        yield 'reserved position bottom-bar (P2)' => [(string) json_encode(['position' => 'bottom-bar'] + $base)];
        yield 'unknown position' => [(string) json_encode(['position' => 'top'] + $base)];
        yield 'unknown trigger' => [(string) json_encode(['trigger' => 'hover'] + $base)];
        yield 'javascript: url' => [(string) json_encode(['link' => ['url' => 'javascript:alert(1)']] + ['enabled' => true, 'content' => ['label' => 'x']])];
        yield 'data: url' => [(string) json_encode(['link' => ['url' => 'data:text/html,x']] + ['enabled' => true, 'content' => ['label' => 'x']])];
        yield 'protocol-relative url' => [(string) json_encode(['link' => ['url' => '//evil.test/x']] + ['enabled' => true, 'content' => ['label' => 'x']])];
        yield 'backslash authority url' => [(string) json_encode(['link' => ['url' => '/\\evil.test']] + ['enabled' => true, 'content' => ['label' => 'x']])];
        yield 'bad accent' => [(string) json_encode(['accent' => 'red'] + $base)];
        yield 'unknown iconId (fail-closed)' => [(string) json_encode(['content' => ['label' => 'x', 'iconId' => 'skull']] + ['enabled' => true, 'link' => ['url' => 'https://x.test']])];
        yield 'enabled without label' => [(string) json_encode(['enabled' => true, 'link' => ['url' => 'https://x.test']])];
        yield 'enabled without url' => [(string) json_encode(['enabled' => true, 'content' => ['label' => 'x']])];
        yield 'conditions.types not array' => [(string) json_encode(['conditions' => ['types' => 'page']] + $base)];
        yield 'label too long' => [(string) json_encode(['enabled' => true, 'content' => ['label' => str_repeat('a', 61)], 'link' => ['url' => 'https://x.test']])];
        yield 'bottomOffset over max' => [(string) json_encode(['bottomOffset' => 9999] + $base)];
        yield 'bottomOffset negative' => [(string) json_encode(['bottomOffset' => -1] + $base)];
        yield 'bottomOffset not int' => [(string) json_encode(['bottomOffset' => '100'] + $base)];
        yield 'dismissible not bool' => [(string) json_encode(['dismissible' => 'yes'] + $base)];
        yield 'delay without seconds' => [(string) json_encode(['trigger' => 'delay'] + $base)];
        yield 'delay seconds too large' => [(string) json_encode(['trigger' => 'delay', 'triggerValue' => 61] + $base)];
        yield 'delay seconds zero' => [(string) json_encode(['trigger' => 'delay', 'triggerValue' => 0] + $base)];
        yield 'delay seconds not int' => [(string) json_encode(['trigger' => 'delay', 'triggerValue' => '5'] + $base)];
        yield 'scroll without px' => [(string) json_encode(['trigger' => 'scroll'] + $base)];
        yield 'scroll px too large' => [(string) json_encode(['trigger' => 'scroll', 'triggerValue' => 5001] + $base)];
        yield 'scroll px zero' => [(string) json_encode(['trigger' => 'scroll', 'triggerValue' => 0] + $base)];
    }

    public function testMailtoAndTelAndRelativeAreAccepted(): void
    {
        foreach (['mailto:a@b.test', 'tel:+81312345678', '/contact', '#book'] as $url) {
            (new FloatingCtaValidator())->validate((string) json_encode([
                'enabled' => true,
                'content' => ['label' => 'x'],
                'link' => ['url' => $url],
            ]));
        }
        $this->addToAssertionCount(1);
    }
}
