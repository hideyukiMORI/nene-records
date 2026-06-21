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
}
