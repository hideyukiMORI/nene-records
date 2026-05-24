<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use NeNeRecords\Entity\EntityRelationQueryParser;
use PHPUnit\Framework\TestCase;

final class EntityRelationQueryParserTest extends TestCase
{
    public function testParseRelationFiltersExtractsFieldKeyAndTargetId(): void
    {
        $filters = EntityRelationQueryParser::parseRelationFilters([
            'entity_type_id' => '1',
            'relation.author' => '5',
            'relation.category' => '12',
        ]);

        self::assertSame(['author' => 5, 'category' => 12], $filters);
    }

    public function testParseRelationFiltersSupportsUnderscoreKeysFromPhpQueryParsing(): void
    {
        $filters = EntityRelationQueryParser::parseRelationFilters([
            'relation_author' => '10',
            'relation_category' => '20',
        ]);

        self::assertSame(['author' => 10, 'category' => 20], $filters);
    }

    public function testParseRelationFiltersSupportsNestedRelationArray(): void
    {
        $filters = EntityRelationQueryParser::parseRelationFilters([
            'relation' => [
                'author' => '7',
            ],
        ]);

        self::assertSame(['author' => 7], $filters);
    }

    public function testParseRelationFiltersIgnoresInvalidValues(): void
    {
        $filters = EntityRelationQueryParser::parseRelationFilters([
            'relation.author' => '0',
            'relation.category' => '-1',
            'relation.empty' => '',
            'relation.bad' => 'abc',
            'relation.' => '1',
            'not-relation.author' => '2',
        ]);

        self::assertSame([], $filters);
    }
}
