<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BlocksField;

use Nene2\Validation\ValidationException;
use NeNeRecords\BlocksField\BlocksDocumentValidator;
use PHPUnit\Framework\TestCase;

final class BlocksDocumentValidatorTest extends TestCase
{
    private BlocksDocumentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new BlocksDocumentValidator();
    }

    public function testAcceptsEmptyDocument(): void
    {
        $this->validator->validate('[]');
        $this->addToAssertionCount(1);
    }

    public function testAcceptsValidTextAndCalloutBlocks(): void
    {
        $json = json_encode([
            ['id' => 'b1', 'type' => 'text', 'data' => ['markdown' => '# Hello']],
            ['id' => 'b2', 'type' => 'callout', 'data' => ['kind' => 'info', 'title' => 'Note', 'body' => 'Body text']],
            ['id' => 'b3', 'type' => 'callout', 'data' => ['kind' => 'danger', 'body' => 'No title is fine']],
        ], JSON_THROW_ON_ERROR);

        $this->validator->validate($json);
        $this->addToAssertionCount(1);
    }

    public function testRejectsNonJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('not json');
    }

    public function testRejectsNonArrayDocument(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('{"id":"b1","type":"text"}');
    }

    public function testRejectsUnknownBlockType(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"b1","type":"spaceship","data":{}}]');
    }

    public function testRejectsBlockMissingId(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"type":"text","data":{"markdown":"hi"}}]');
    }

    public function testRejectsBlockWithNonObjectData(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"b1","type":"text","data":"nope"}]');
    }

    public function testRejectsTextBlockWithoutMarkdown(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"b1","type":"text","data":{}}]');
    }

    public function testRejectsCalloutWithInvalidKind(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"b1","type":"callout","data":{"kind":"chaos","body":"x"}}]');
    }

    public function testRejectsCalloutWithoutBody(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"b1","type":"callout","data":{"kind":"info"}}]');
    }

    public function testRejectsTooManyBlocks(): void
    {
        $blocks = [];
        for ($i = 0; $i <= BlocksDocumentValidator::MAX_BLOCKS; $i++) {
            $blocks[] = ['id' => 'b' . $i, 'type' => 'text', 'data' => ['markdown' => 'x']];
        }

        $this->expectException(ValidationException::class);
        $this->validator->validate(json_encode($blocks, JSON_THROW_ON_ERROR));
    }

    public function testAcceptsValidHeroBlock(): void
    {
        $json = json_encode([
            ['id' => 'h1', 'type' => 'hero', 'data' => [
                'variant' => 'standard',
                'kicker' => 'Spring',
                'heading' => 'New *releases*',
                'lead' => 'Lead text.',
                'ctaLabel' => 'Browse',
                'ctaUrl' => '/releases',
                'ghostLabel' => 'Archive',
                'ghostUrl' => 'https://example.com/archive',
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->validator->validate($json);
        $this->addToAssertionCount(1);
    }

    public function testRejectsHeroWithoutHeading(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"h1","type":"hero","data":{"variant":"standard"}}]');
    }

    public function testRejectsHeroWithInvalidVariant(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"h1","type":"hero","data":{"variant":"wild","heading":"x"}}]');
    }

    public function testRejectsHeroWithUnsafeCtaUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"h1","type":"hero","data":{"variant":"standard","heading":"x","ctaUrl":"javascript:alert(1)"}}]',
        );
    }

    public function testAcceptsHeroWithMedia(): void
    {
        $json = json_encode([
            ['id' => 'h1', 'type' => 'hero', 'data' => [
                'variant' => 'standard',
                'heading' => 'Title',
                'media' => ['mediaId' => '42', 'url' => '/media/2026/06/cover.png', 'alt' => 'Cover'],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->validator->validate($json);
        $this->addToAssertionCount(1);
    }

    public function testRejectsHeroMediaProtocolRelativeUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"h1","type":"hero","data":{"variant":"standard","heading":"x","media":{"mediaId":"1","url":"//evil.example/x.png"}}}]',
        );
    }

    public function testAcceptsHeroMediaHttpsUrl(): void
    {
        // Object-storage / CDN drivers (S3) return absolute https URLs.
        $this->validator->validate(
            '[{"id":"h1","type":"hero","data":{"variant":"standard","heading":"x","media":{"mediaId":"1","url":"https://cdn.example.com/media/2026/06/x.png"}}}]',
        );
        $this->addToAssertionCount(1);
    }

    public function testRejectsHeroCtaProtocolRelativeUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"h1","type":"hero","data":{"variant":"standard","heading":"x","ctaUrl":"//evil.example"}}]',
        );
    }

    public function testAcceptsValidGalleryBlock(): void
    {
        $json = json_encode([
            ['id' => 'g1', 'type' => 'gallery', 'data' => [
                'layout' => 'carousel',
                'items' => [
                    ['mediaId' => '1', 'url' => '/media/2026/06/a.png', 'alt' => 'A', 'caption' => 'First'],
                    ['mediaId' => '2', 'url' => '/media/2026/06/b.png', 'alt' => 'B'],
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->validator->validate($json);
        $this->addToAssertionCount(1);
    }

    public function testRejectsGalleryWithoutItems(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"g1","type":"gallery","data":{"layout":"grid","items":[]}}]');
    }

    public function testRejectsGalleryItemWithoutAlt(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"g1","type":"gallery","data":{"layout":"carousel","items":[{"mediaId":"1","url":"/media/2026/06/a.png"}]}}]',
        );
    }

    public function testRejectsGalleryItemInsecureUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"g1","type":"gallery","data":{"layout":"grid","items":[{"mediaId":"1","url":"//evil.example/a.png","alt":"A"}]}}]',
        );
    }

    public function testAcceptsValidChartBlock(): void
    {
        $json = json_encode([
            ['id' => 'k1', 'type' => 'chart', 'data' => [
                'chartType' => 'bar',
                'title' => 'Monthly',
                'series' => [
                    ['label' => 'Jan', 'value' => 4],
                    ['label' => 'Feb', 'value' => 6.5],
                ],
                'summary' => 'Up from Jan to Feb.',
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->validator->validate($json);
        $this->addToAssertionCount(1);
    }

    public function testRejectsChartWithoutSummary(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"k1","type":"chart","data":{"chartType":"bar","series":[{"label":"A","value":1},{"label":"B","value":2}]}}]',
        );
    }

    public function testRejectsChartWithTooFewPoints(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"k1","type":"chart","data":{"chartType":"line","summary":"x","series":[{"label":"A","value":1}]}}]',
        );
    }

    public function testRejectsChartPointWithNonNumericValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"k1","type":"chart","data":{"chartType":"bar","summary":"x","series":[{"label":"A","value":"NaN"},{"label":"B","value":2}]}}]',
        );
    }

    public function testAcceptsValidGroupBlockWithLeafChildren(): void
    {
        $json = json_encode([
            ['id' => 'g1', 'type' => 'group', 'data' => [
                'tone' => 'card',
                'children' => [
                    ['id' => 'c1', 'type' => 'text', 'data' => ['markdown' => 'Inside']],
                    ['id' => 'c2', 'type' => 'callout', 'data' => ['kind' => 'info', 'body' => 'Note']],
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->validator->validate($json);
        $this->addToAssertionCount(1);
    }

    public function testAcceptsEmptyGroup(): void
    {
        $this->validator->validate('[{"id":"g1","type":"group","data":{"tone":"plain","children":[]}}]');
        $this->addToAssertionCount(1);
    }

    public function testRejectsGroupWithInvalidTone(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate('[{"id":"g1","type":"group","data":{"tone":"wild","children":[]}}]');
    }

    public function testRejectsContainerNestedInsideContainer(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"g1","type":"group","data":{"tone":"plain","children":[{"id":"g2","type":"group","data":{"tone":"plain","children":[]}}]}}]',
        );
    }

    public function testRejectsGroupChildWithInvalidData(): void
    {
        // A leaf child with invalid data propagates an error from the nested path.
        $this->expectException(ValidationException::class);
        $this->validator->validate(
            '[{"id":"g1","type":"group","data":{"tone":"plain","children":[{"id":"c1","type":"text","data":{}}]}}]',
        );
    }
}
