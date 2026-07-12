<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgExport;

use NeNeRecords\OrgExport\BlocksMediaRewriter;
use PHPUnit\Framework\TestCase;

/**
 * #795: rewriting media references embedded in a blocks document on org transport.
 * mediaId is remapped via the media map; absolute same-origin /media URLs are
 * relativized; everything else is preserved verbatim.
 */
final class BlocksMediaRewriterTest extends TestCase
{
    /** @var array<int, int> */
    private const MEDIA_MAP = [60 => 900, 61 => 901];

    public function testRemapsHeroMediaIdAndRelativizesAbsoluteUrl(): void
    {
        $doc = json_encode([[
            'id'   => 'b1',
            'type' => 'hero',
            'data' => [
                'variant' => 'center',
                'heading' => 'Hi',
                'media'   => ['mediaId' => '60', 'url' => 'https://source.nene-records.com/media/2026/x.png', 'alt' => 'x'],
            ],
        ]], JSON_UNESCAPED_SLASHES);

        $out = json_decode(BlocksMediaRewriter::rewrite((string) $doc, self::MEDIA_MAP), true);

        self::assertSame('900', $out[0]['data']['media']['mediaId']);
        self::assertSame('/media/2026/x.png', $out[0]['data']['media']['url']);
        self::assertSame('x', $out[0]['data']['media']['alt']); // untouched
    }

    public function testRemapsGalleryItems(): void
    {
        $doc = json_encode([[
            'id'   => 'g1',
            'type' => 'gallery',
            'data' => [
                'layout' => 'grid',
                'items'  => [
                    ['mediaId' => '60', 'url' => '/media/a.png', 'alt' => 'a'],
                    ['mediaId' => '61', 'url' => 'https://old-host.example/media/b.png', 'alt' => 'b'],
                ],
            ],
        ]], JSON_UNESCAPED_SLASHES);

        $out   = json_decode(BlocksMediaRewriter::rewrite((string) $doc, self::MEDIA_MAP), true);
        $items = $out[0]['data']['items'];

        self::assertSame('900', $items[0]['mediaId']);
        self::assertSame('/media/a.png', $items[0]['url']); // already relative
        self::assertSame('901', $items[1]['mediaId']);
        self::assertSame('/media/b.png', $items[1]['url']); // relativized
    }

    public function testRemapsMediaNestedInsideColumnsAndGroup(): void
    {
        $doc = json_encode([
            [
                'id'   => 'c1',
                'type' => 'columns',
                'data' => ['columns' => [
                    ['children' => [[
                        'id'   => 'h1',
                        'type' => 'hero',
                        'data' => ['media' => ['mediaId' => '60', 'url' => '/media/x.png']],
                    ]]],
                ]],
            ],
            [
                'id'   => 'grp',
                'type' => 'group',
                'data' => ['tone' => 'muted', 'children' => [[
                    'id'   => 'gal',
                    'type' => 'gallery',
                    'data' => ['items' => [['mediaId' => '61', 'url' => '/media/y.png']]],
                ]]],
            ],
        ], JSON_UNESCAPED_SLASHES);

        $out = json_decode(BlocksMediaRewriter::rewrite((string) $doc, self::MEDIA_MAP), true);

        self::assertSame('900', $out[0]['data']['columns'][0]['children'][0]['data']['media']['mediaId']);
        self::assertSame('901', $out[1]['data']['children'][0]['data']['items'][0]['mediaId']);
    }

    public function testLeavesExternalNonMediaUrlUntouched(): void
    {
        $doc = json_encode([[
            'id'   => 'h',
            'type' => 'hero',
            'data' => ['media' => ['mediaId' => '999', 'url' => 'https://cdn.example.com/images/x.png']],
        ]], JSON_UNESCAPED_SLASHES);

        $out = json_decode(BlocksMediaRewriter::rewrite((string) $doc, self::MEDIA_MAP), true);

        // mediaId 999 not in the map → kept; external non-/media URL → kept.
        self::assertSame('999', $out[0]['data']['media']['mediaId']);
        self::assertSame('https://cdn.example.com/images/x.png', $out[0]['data']['media']['url']);
    }

    public function testReturnsBodyVerbatimWhenNothingChanges(): void
    {
        // A text block with no media, and pretty-printed JSON: the exact bytes must
        // come back so an untouched document is not reformatted.
        $doc = "[\n  {\"id\": \"t1\", \"type\": \"text\", \"data\": {\"markdown\": \"hello\"}}\n]";

        self::assertSame($doc, BlocksMediaRewriter::rewrite($doc, self::MEDIA_MAP));
    }

    public function testLeavesNonBlocksDocumentVerbatim(): void
    {
        // footer_config-shaped object (not a JSON array of blocks) → untouched.
        $obj = '{"social":[],"legalLinks":[],"showPoweredBy":true}';
        self::assertSame($obj, BlocksMediaRewriter::rewrite($obj, self::MEDIA_MAP));
        self::assertSame('[]', BlocksMediaRewriter::rewrite('[]', self::MEDIA_MAP));
        self::assertSame('not json', BlocksMediaRewriter::rewrite('not json', self::MEDIA_MAP));
    }

    public function testEmptyMediaMapIsNoOp(): void
    {
        $doc = json_encode([[
            'id'   => 'h',
            'type' => 'hero',
            'data' => ['media' => ['mediaId' => '60', 'url' => 'https://source/media/x.png']],
        ]], JSON_UNESCAPED_SLASHES);

        self::assertSame((string) $doc, BlocksMediaRewriter::rewrite((string) $doc, []));
    }
}
