<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use NeNeRecords\Entity\ExcerptResolver;
use NeNeRecords\Entity\ListEntityItem;
use NeNeRecords\Entity\MarkdownExcerpt;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use PHPUnit\Framework\TestCase;

final class ExcerptResolverTest extends TestCase
{
    public function testMarkdownStripFlattensSyntax(): void
    {
        $md = "# Heading\n\nSome **bold** and a [link](https://x). More text.";
        self::assertSame('Heading Some bold and a link. More text.', MarkdownExcerpt::fromMarkdown($md, 999));
    }

    public function testTruncateAddsEllipsis(): void
    {
        self::assertSame('abcde…', MarkdownExcerpt::truncate('abcdefghij', 5));
        self::assertSame('short', MarkdownExcerpt::truncate('short', 99));
    }

    public function testAutoPrefersMetaThenBody(): void
    {
        $items = [
            $this->item(1, 'Meta summary here'),
            $this->item(2, null), // no meta → falls back to body
        ];
        $resolver = $this->resolver(
            bodies: [2 => "## Body title\n\nBody **paragraph** text."],
            source: 'auto',
        );

        $out = $resolver->resolve($items);

        self::assertSame('Meta summary here', $out[1]);
        self::assertSame('Body title Body paragraph text.', $out[2]);
    }

    public function testBodySourceIgnoresMeta(): void
    {
        $items = [$this->item(1, 'Meta summary')];
        $out = $this->resolver(bodies: [1 => 'Plain body.'], source: 'body')->resolve($items);
        self::assertSame('Plain body.', $out[1]);
    }

    public function testMetaSourceIgnoresBody(): void
    {
        $items = [$this->item(1, 'Meta only')];
        $out = $this->resolver(bodies: [1 => 'Body ignored.'], source: 'meta')->resolve($items);
        self::assertSame('Meta only', $out[1]);
    }

    private function item(int $id, ?string $metaDescription): ListEntityItem
    {
        return new ListEntityItem(
            id: $id,
            entityTypeId: 1,
            slug: 's' . $id,
            permalink: null,
            status: 'published',
            publishedAtIso: null,
            isDeleted: false,
            deletedAtIso: null,
            metaDescription: $metaDescription,
        );
    }

    /**
     * @param array<int, string> $bodies entityId => markdown body
     */
    private function resolver(array $bodies, string $source): ExcerptResolver
    {
        $seed = [];
        $id = 1;
        foreach ($bodies as $entityId => $value) {
            $seed[] = new TextField(entityId: $entityId, fieldKey: 'body', value: $value, id: $id++);
        }

        $settings = new InMemorySettingRepository([
            new SettingDef('excerpt_source', 'text', 'auto', false, 'Excerpt source'),
            new SettingDef('excerpt_length', 'text', '160', false, 'Excerpt length'),
        ]);
        $settings->applyValueDirect('excerpt_source', $source, null);

        return new ExcerptResolver(new InMemoryTextFieldRepository($seed), $settings);
    }
}
