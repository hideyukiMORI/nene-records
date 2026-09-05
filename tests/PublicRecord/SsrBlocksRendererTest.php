<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\ContactSubmission\SubmitContactFormHandler;
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
        self::assertSame('', $this->renderer()->render(self::LEGACY_DOCUMENT)->html);
    }

    /**
     * The positive control for the test above. Without this, "renders nothing" would also
     * pass if the renderer were broken and rendered nothing at all, ever.
     */
    public function testAContactFormDocumentDoesChangeTheOutput(): void
    {
        $html = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"ayane-contact"}}]')->html;

        self::assertNotSame('', $html);
        self::assertStringContainsString('<form', $html);
    }

    /**
     * hub 検収条件2: the no-JS form posts at the records-side proxy, never at the issuing
     * product — a direct post would mean handing the browser the connect-token.
     */
    public function testFormPostsToTheRecordsProxyAndLeaksNothing(): void
    {
        $html = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"ayane-contact"}}]')->html;

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
        $html = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"ayane-contact"}}]')->html;

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

        $html = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

        self::assertStringContainsString('name="nickname"', $html);
        self::assertStringContainsString('type="text"', $html);
    }

    /**
     * #1066: the issuing product declares its own honeypot so records *knows about* it, not so
     * records shows it. Before the fix it fell through to the `default` arm above and printed a
     * visible, empty-labelled text box whose contents the issuing product then discarded without
     * an error.
     *
     * Both halves matter: deleting every honeypot would also satisfy the first assertion, so the
     * second pins that records' own hidden trap survives.
     */
    public function testIssuingProductHoneypotIsNotRenderedButRecordsOwnTrapStillIs(): void
    {
        $renderer = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('message', 'Message', 'textarea', true),
            new ContactFormField('website', '', ContactFormField::TYPE_HONEYPOT, false),
        ]));

        $html = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

        // Nothing a person could see or type into. `name="website"` is not a substring of
        // `name="website_url"`, so this does not accidentally assert against records' own trap.
        self::assertStringNotContainsString('name="website"', $html);
        self::assertStringNotContainsString('contact-c-website"', $html);

        // records' own hidden trap is still emitted, and the real fields still render.
        self::assertStringContainsString('name="' . SubmitContactFormHandler::HONEYPOT_FIELD . '"', $html);
        self::assertStringContainsString('name="message"', $html);
    }

    public function testSchemaValuesAreEscapedIntoAttributesAndText(): void
    {
        $renderer = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('evil" onfocus="alert(1)', '<script>alert(1)</script>', 'text', false),
        ], submitLabel: '"><script>x</script>'));

        $html = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

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

        $html = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"typo"}}]')->html;

        // An empty region would look like a page that simply has no form; the author would
        // never learn the key was wrong.
        self::assertStringContainsString('contact-form--unavailable', $html);
        self::assertStringNotContainsString('<form', $html);
    }

    public function testConsentCheckboxAppearsOnlyWhenTheFormRequiresIt(): void
    {
        $without = $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;
        $with = $this->renderer(new ContactFormSchema('k', [], consentRequired: true))
            ->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

        self::assertStringNotContainsString('name="consent"', $without);
        self::assertStringContainsString('name="consent"', $with);
    }

    public function testMalformedOrEmptyDocumentsRenderNothing(): void
    {
        foreach (['', '   ', 'not json', '{"not":"a list"}', '[42]', '[{"type":"contact-form"}]'] as $document) {
            self::assertSame('', $this->renderer()->render($document)->html, 'Document: ' . $document);
        }
    }

    public function testBlockWithoutAFormKeyRendersNothing(): void
    {
        self::assertSame('', $this->renderer()->render('[{"id":"c","type":"contact-form","data":{"variant":"inline"}}]')->html);
    }

    public function testTwoFormsOnOnePageDoNotShareElementIds(): void
    {
        // Duplicate ids break every `<label for>` association silently — clicking a label
        // focuses the first match, which is in the other form.
        $document = '[{"id":"one","type":"contact-form","data":{"formKey":"k"}},'
            . '{"id":"two","type":"contact-form","data":{"formKey":"k"}}]';

        $html = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('email', 'Email', 'email', true),
        ]))->render($document, 'sections')->html;

        preg_match_all('/id="([^"]+)"/', $html, $matches);
        $ids = $matches[1];

        self::assertCount(2, $ids);
        self::assertSame($ids, array_unique($ids), 'Generated element ids must be unique on a page.');
    }

    public function testTwoBlocksFieldsOnOnePageDoNotShareElementIds(): void
    {
        $document = '[{"id":"one","type":"contact-form","data":{"formKey":"k"}}]';
        $renderer = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('email', 'Email', 'email', true),
        ]));

        $first = $renderer->render($document, 'sections')->html;
        $second = $renderer->render($document, 'footer')->html;

        self::assertNotSame(
            $this->firstId($first),
            $this->firstId($second),
            'The scope must disambiguate ids across blocks fields.',
        );
    }

    public function testIdsBuiltFromCallerSuppliedStringsStaySafe(): void
    {
        $html = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('bad" onfocus="alert(1)', 'Bad', 'text', false),
        ]))->render('[{"id":"a\" onload=\"x","type":"contact-form","data":{"formKey":"k"}}]', 'sc"ope')->html;

        // The escaped text `onfocus=` may legitimately appear inside an attribute *value*;
        // what must never appear is a real attribute break, i.e. an unescaped quote followed
        // by a handler. Assert on the dangerous shape, not on the substring.
        self::assertDoesNotMatchRegularExpression('/"\s+on[a-z]+\s*=/i', $html);

        preg_match_all('/id="([^"]*)"/', $html, $matches);
        self::assertNotSame([], $matches[1]);

        foreach ($matches[1] as $id) {
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $id, 'Generated ids must be attribute-safe.');
        }
    }

    public function testRequiredSelectGetsAnEmptyFirstOptionSoRequiredMeansSomething(): void
    {
        // Without a placeholder the first option is already selected, so the browser treats
        // the control as filled in and `required` never fires.
        $html = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('topic', 'Topic', 'select', true, ['Sales', 'Support']),
        ]))->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

        self::assertStringContainsString('<option value="" selected disabled></option>', $html);
    }

    public function testOptionalSelectHasNoPlaceholderOption(): void
    {
        $html = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('topic', 'Topic', 'select', false, ['Sales']),
        ]))->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

        self::assertStringNotContainsString('<option value="" selected disabled>', $html);
    }

    public function testWordingSuppliedByTheIssuingProductIsUsedVerbatim(): void
    {
        // The consent sentence is contact's legal text and the button is its copy; records
        // must not overwrite either with English.
        $html = $this->renderer(new ContactFormSchema('k', [], consentRequired: true, submitLabel: '送信する', consentLabel: '個人情報の取り扱いに同意します'))
            ->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

        self::assertStringContainsString('送信する', $html);
        self::assertStringContainsString('個人情報の取り扱いに同意します', $html);
        self::assertStringNotContainsString('lang="en"', $html);
    }

    public function testEnglishFallbackWordingIsTaggedAsEnglish(): void
    {
        // records has no SSR message catalogue yet (#1034). Until it does, the fallback must
        // not claim to be in the page's language — crawlers read this markup.
        $html = $this->renderer(new ContactFormSchema('k', [], consentRequired: true))
            ->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]')->html;

        self::assertStringContainsString('<span lang="en">Send</span>', $html);
        self::assertStringContainsString('lang="en">I agree', $html);
    }

    // ── bootstrap 同梱（hub 裁定 (c)・2026-07-30）────────────────────────────

    /**
     * hub pin 1: only the resolved *public* schema travels in the bootstrap. No token, no
     * internal URL, no reference to the connect-token module — same shelf as the #1029 pins.
     */
    public function testBootstrapCarriesOnlyThePublicSchema(): void
    {
        $result = $this->renderer(new ContactFormSchema('ayane-contact', [
            new ContactFormField('email', 'Email', 'email', true),
        ], consentRequired: true, submitLabel: '送信', consentLabel: '同意します'))
            ->render('[{"id":"c","type":"contact-form","data":{"formKey":"ayane-contact"}}]');

        self::assertArrayHasKey('ayane-contact', $result->contactForms);

        $encoded = (string) json_encode($result->contactForms);

        foreach (['token', 'Authorization', 'Bearer', 'ConnectToken', 'org_connect_tokens', 'http://', 'https://'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $encoded, $forbidden . ' must not reach the bootstrap.');
        }

        // What it *does* carry: enough to draw the identical form, and the proxy path so the
        // client cannot invent a different destination.
        $schema = $result->contactForms['ayane-contact'];
        self::assertSame(ContactSubmissionProxyRoute::PATH, $schema['submitPath']);
        self::assertSame('送信', $schema['submitLabel']);
        self::assertSame([['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'options' => []]], $schema['fields']);
    }

    /**
     * The point of (c): the schema handed to the client is the same one the server drew from,
     * so the crawlable form and the hydrated form cannot disagree. Asserted by resolving once
     * and checking every field of the HTML is described by the bootstrap entry.
     */
    public function testBootstrapDescribesExactlyWhatTheServerRendered(): void
    {
        $result = $this->renderer(new ContactFormSchema('k', [
            new ContactFormField('email', 'Email', 'email', true),
            new ContactFormField('message', 'Message', 'textarea', false),
        ]))->render('[{"id":"c","type":"contact-form","data":{"formKey":"k"}}]');

        preg_match_all('/name="([^"]+)"/', $result->html, $matches);
        // Control inputs are records' own, not the schema's: the hidden form key and the
        // honeypot (#1031) are excluded so this stays a check on schema parity.
        $rendered = array_values(array_diff($matches[1], ['form_key', SubmitContactFormHandler::HONEYPOT_FIELD]));

        $described = array_map(
            static fn (array $field): string => (string) $field['key'],
            $result->contactForms['k']['fields'],
        );

        self::assertSame($described, $rendered);
    }

    public function testAFormThatCouldNotBeResolvedContributesNothingToTheBootstrap(): void
    {
        $renderer = new SsrBlocksRenderer(new class () implements ContactFormSchemaProviderInterface {
            public function schemaFor(string $formKey): ?ContactFormSchema
            {
                return null;
            }
        });

        $result = $renderer->render('[{"id":"c","type":"contact-form","data":{"formKey":"typo"}}]');

        // The visible failure notice is server-only; there is nothing for the client to draw.
        self::assertSame([], $result->contactForms);
        self::assertStringContainsString('contact-form--unavailable', $result->html);
    }

    public function testLegacyDocumentsAddNothingToTheBootstrap(): void
    {
        self::assertSame([], $this->renderer()->render(self::LEGACY_DOCUMENT)->contactForms);
    }

    private function firstId(string $html): string
    {
        preg_match_all('/id="([^"]+)"/', $html, $matches);
        $ids = $matches[1];

        self::assertNotSame([], $ids, 'The rendered form must carry at least one element id.');

        return $ids[0];
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
