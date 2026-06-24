<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\WxrImport\WxrImportPlanner;
use NeNeRecords\WxrImport\WxrParser;
use PHPUnit\Framework\TestCase;

final class WxrImportPlannerTest extends TestCase
{
    private function plan(): \NeNeRecords\WxrImport\WxrImportPlan
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/sample.wxr.xml');
        self::assertNotFalse($xml);
        $doc = (new WxrParser())->parse($xml);

        return (new WxrImportPlanner())->plan($doc);
    }

    public function testPlansPostsAndPagesAndSkipsAttachments(): void
    {
        $plan = $this->plan();

        // hello-world(post), about(page), draft-post(post), no-slug(post) = 4 planned.
        self::assertCount(4, $plan->plannedItems);
        // attachment not an entity; imported into the media library on execute.
        self::assertCount(1, $plan->skippedItems);
        self::assertSame('image.jpg', $plan->skippedItems[0]->title);
        self::assertStringContainsString('メディア', $plan->skippedItems[0]->reason);

        self::assertSame(['posts' => 3, 'pages' => 1], $plan->countsByEntityType);
        self::assertSame(['published' => 3, 'draft' => 1], $plan->countsByStatus);
    }

    public function testMapsTypesStatusesAndTags(): void
    {
        $plan = $this->plan();
        $bySlug = [];
        foreach ($plan->plannedItems as $item) {
            $bySlug[$item->slug] = $item;
        }

        self::assertSame('posts', $bySlug['hello-world']->entityTypeSlug);
        self::assertSame('published', $bySlug['hello-world']->status);
        self::assertSame(['news', 'php'], $bySlug['hello-world']->tagSlugs);

        self::assertSame('pages', $bySlug['about']->entityTypeSlug);
        self::assertSame('draft', $bySlug['draft-post']->status);

        // Categories + post_tags are merged into NeNe tags.
        self::assertContains('news', $plan->tagSlugs);
        self::assertContains('php', $plan->tagSlugs);
    }

    public function testDerivesSlugFromTitleWithWarning(): void
    {
        $plan = $this->plan();
        $bySlug = [];
        foreach ($plan->plannedItems as $item) {
            $bySlug[$item->slug] = $item;
        }

        // "No Slug Here" → derived slug + a warning.
        self::assertArrayHasKey('no-slug-here', $bySlug);
        self::assertNotEmpty($plan->warnings);
        self::assertStringContainsString('No Slug Here', implode("\n", $plan->warnings));
    }
}
