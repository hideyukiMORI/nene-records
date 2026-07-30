<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\ContactFormField;
use NeNeRecords\PublicRecord\ContactFormSchema;
use NeNeRecords\PublicRecord\ContactFormSchemaProviderInterface;
use NeNeRecords\PublicRecord\ContactSubmissionProxyRoute;
use NeNeRecords\PublicRecord\SsrBlocksRenderer;
use PHPUnit\Framework\TestCase;

final class SsrBlocksRendererTest extends TestCase
{
    /**
     * A document made only of the block types that existed before #1030. Used as the
     * unchanged-output control below.
     */
    private const LEGACY_DOCUMENT = <<<'JSON'
        [
          {"id":"t","type":"text","data":{"markdown":"hello"}},
          {"id":"c","type":"callout","data":{"kind":"info","body":"note"}},
          {"id":"s","type":"spacer","data":{"size":"md"}},
          {"id":"d","type":"divider","data":{}},
          {"id":"g","type":"group","data":{"tone":"card","children":[{"id":"t2","type":"text","data":{"markdown":"inner"}}]}}
        ]
        JSON;

    /**
     * hub 検収条件1: existing documents must render exactly as before — asserted, not claimed.
     */
    public function testExistingBlockTypesRenderNothingOnTheServer(): void
    {
        self::assertSame('', $this->renderer()->render(self::LEGACY_DOCUMENT));
    }

    /**
     * The positive control for the test above. Without this, "renders nothing" would also
     * pass if the renderer were broken and rendered nothing at all, ever.
     */
    public function testAContactFormDocumentDoesChangeTheOutput(): void
    {
        $html = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"ayane-contact"}}]');

        self::assertNotSame('', $html);
        self::assertStringContainsString('<form', $html);
    }

    /**
     * hub 検収条件2: the no-JS form posts at the records-side proxy, never at the issuing
     * product — a direct post would mean handing the browser the connect-token.
     */
    public function testFormPostsToTheRecordsProxyAndLeaksNothing(): void
    {
        $html = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"ayane-contact"}}]');

        self::assertStringContainsString('action="' . ContactSubmissionProxyRoute::PATH . '"', $html);
        self::assertStringContainsString('method="post"', $html);

        // Nothing that points at contact, and nothing token-shaped, may appear in public HTML.
        self::assertStringNotContainsString('http://', $html);
        self::assertStringNotContainsString('https://', $html);
        self::assertStringNotContainsString('Authorization', $html);
        self::assertStringNotContainsString('Bearer', $html);
        self::assertStringNotContainsString('token', $html);
    }

    public function testRendersOneControlPerSchemaFieldWithTypesAndRequiredness(): void
    {
        $html = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"ayane-contact"}}]');

        self::assertStringContainsString('name="name"', $html);
        self::assertStringContainsString('type="email"', $html);
        self::assertStringContainsString('<textarea', $html);
        self::assertStringContainsString('required', $html);
        self::assertStringContainsString('value="ayane-contact"', $html);
    }

    public function testUnknownFieldTypeBecomesATextInputRatherThanDisappearing(): void
    {
        $renderer = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('nickname', 'Nickname', 'colour-picker', false),
        ]));

        $html = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]');

        self::assertStringContainsString('name="nickname"', $html);
        self::assertStringContainsString('type="text"', $html);
    }

    public function testSchemaValuesAreEscapedIntoAttributesAndText(): void
    {
        $renderer = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('evil" onfocus="alert(1)', '<script>alert(1)</script>', 'text', false),
        ], submitLabel: '"><script>x</script>'));

        $html = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]');

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('onfocus="alert(1)"', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testMissingSchemaFailsVisiblyInsteadOfRenderingNothing(): void
    {
        $renderer = new SsrBlocksRenderer(new class () implements ContactFormSchemaProviderInterface {
            public function schemaFor(string $formKey): ?ContactFormSchema
            {
                return null;
            }
        });

        $html = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"typo"}}]');

        // An empty region would look like a page that simply has no form; the author would
        // never learn the key was wrong.
        self::assertStringContainsString('contact-form--unavailable', $html);
        self::assertStringNotContainsString('<form', $html);
    }

    public function testConsentCheckboxAppearsOnlyWhenTheFormRequiresIt(): void
    {
        $without = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]');
        $with = $this->renderer(new ContactFormSchema('k', [], consentRequired: true))
            ->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]');

        self::assertStringNotContainsString('name="consent"', $without);
        self::assertStringContainsString('name="consent"', $with);
    }

    public function testMalformedOrEmptyDocumentsRenderNothing(): void
    {
        foreach (['', '   ', 'not json', '{"not":"a list"}', '[42]', '[{"type":"contact-form"}]'] as $document) {
            self::assertSame('', $this->renderer()->render($document), 'Document: ' . $document);
        }
    }

    public function testBlockWithoutAFormKeyRendersNothing(): void
    {
        self::assertSame('', $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"variant":"inline"}}]'));
    }

    private function renderer(?ContactFormSchema $schema = null): SsrBlocksRenderer
    {
        $resolved = $schema ?? new ContactFormSchema('ayane-contact', [
            new ContactFormField('name', 'Your name', 'text', true),
            new ContactFormField('email', 'Email', 'email', true),
            new ContactFormField('message', 'Message', 'textarea', true),
        ]);

        return new SsrBlocksRenderer(new class ($resolved) implements ContactFormSchemaProviderInterface {
            public function __construct(private readonly ContactFormSchema $schema)
            {
            }

            public function schemaFor(string $formKey): ContactFormSchema
            {
                return $this->schema;
            }
        });
    }
}
