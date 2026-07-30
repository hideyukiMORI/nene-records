<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Renders the *server-renderable* block types of a blocks document into HTML.
 *
 * ## Why this is an allow-list and not a port of the client renderer
 *
 * Blocks are rendered by React (`BlocksRenderer.tsx`) once the SPA hydrates. Re-implementing
 * all nine types here would create two renderers to keep in step forever. Instead this class
 * knows exactly the types that must exist *before* JavaScript runs — today only
 * `contact-form`, which has to be crawlable and has to work with JS off — and emits nothing
 * for every other type.
 *
 * That "nothing" is the safety property: for a document made of the existing types this
 * renderer returns an empty string, so the public HTML of every record written so far is
 * byte-identical to what it was before this class existed. `SsrBlocksRendererTest` pins that,
 * together with a positive control showing a contact-form document *does* change.
 *
 * Everything emitted here is escaped. No block type may produce caller-supplied markup.
 */
final readonly class SsrBlocksRenderer
{
    private const MAX_BLOCKS = 200;

    public function __construct(
        private ContactFormSchemaProviderInterface $schemas,
    ) {
    }

    /**
     * @param string $documentJson the stored blocks document; anything unparseable renders empty
     * @param string $scope        disambiguates generated element ids when more than one blocks
     *                             field is rendered on a page — pass the field key
     */
    public function render(string $documentJson, string $scope = ''): string
    {
        if (trim($documentJson) === '') {
            return '';
        }

        $decoded = json_decode($documentJson, true);

        if (!is_array($decoded) || !array_is_list($decoded)) {
            return '';
        }

        $html = '';
        $scanned = 0;

        foreach ($decoded as $index => $block) {
            if ($scanned >= self::MAX_BLOCKS) {
                break;
            }

            ++$scanned;

            if (!is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? null;
            $data = $block['data'] ?? null;

            if (!is_string($type) || !is_array($data)) {
                continue;
            }

            // This match *is* the allow-list. Every type not named here is rendered by the
            // client renderer after hydration, and emitting nothing for it is what keeps the
            // server-rendered HTML of every pre-#1030 document unchanged.
            $html .= match ($type) {
                'contact-form' => $this->renderContactForm($data, $this->idPrefix($scope, $block, $index)),
                default => '',
            };
        }

        return $html;
    }

    /**
     * Element ids have to be unique across the whole page, not just within one form: two
     * contact blocks, or a second blocks field using the same field keys, would otherwise
     * produce duplicate ids and silently break every `<label for>` association.
     *
     * @param array<array-key, mixed> $block
     */
    private function idPrefix(string $scope, array $block, int|string $index): string
    {
        $blockId = $block['id'] ?? null;
        $unique = is_string($blockId) && $blockId !== '' ? $blockId : (string) $index;

        return $this->slug($scope) . '-' . $this->slug($unique);
    }

    /** Reduces a caller-supplied string to characters that are safe in an id attribute. */
    private function slug(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '', $value);

        return is_string($slug) && $slug !== '' ? $slug : '0';
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function renderContactForm(array $data, string $idPrefix): string
    {
        $formKey = $data['formKey'] ?? null;

        if (!is_string($formKey) || $formKey === '') {
            return '';
        }

        $schema = $this->schemas->schemaFor($formKey);

        if ($schema === null) {
            // Fail-visible: an author who mistyped the key, or an outage at the issuing
            // product, must be able to see that something is wrong. An empty region would
            // look like a page that simply has no form.
            return '<div class="block block--contact-form contact-form--unavailable">'
                . '<p lang="en">This form is temporarily unavailable.</p>'
                . '</div>';
        }

        $fields = '';

        foreach ($schema->fields as $field) {
            $fields .= $this->renderField($field, $idPrefix);
        }

        if ($schema->consentRequired) {
            // The consent sentence is the issuing product's legal text. records only supplies
            // a fallback, and marks it `lang="en"` so the markup does not claim to be in the
            // page's language when it is not (SSR i18n gap: #1034).
            $fields .= '<p class="contact-form__field contact-form__field--consent">'
                . '<label><input type="checkbox" name="consent" value="1" required> '
                . $this->wording($schema->consentLabel, 'I agree to the processing of my message.')
                . '</label></p>';
        }

        // The action is always the records-side proxy. Posting straight at the issuing
        // product would mean handing the browser the connect-token.
        return '<form class="block block--contact-form" method="post" action="'
            . $this->escape(ContactSubmissionProxyRoute::PATH) . '">'
            . '<input type="hidden" name="form_key" value="' . $this->escape($schema->formKey) . '">'
            . $fields
            . '<p class="contact-form__actions"><button type="submit">'
            . $this->wording($schema->submitLabel, 'Send')
            . '</button></p>'
            . '</form>';
    }

    private function renderField(ContactFormField $field, string $idPrefix): string
    {
        $id = 'contact-' . $idPrefix . '-' . $this->slug($field->key);
        $required = $field->required ? ' required' : '';
        $label = '<label for="' . $id . '">' . $this->escape($field->label) . '</label>';

        $control = match ($field->type) {
            'textarea' => '<textarea id="' . $id . '" name="' . $this->escape($field->key) . '" rows="6"' . $required . '></textarea>',
            'select' => $this->renderSelect($field, $id, $required),
            'email', 'tel', 'url' => '<input type="' . $this->escape($field->type) . '" id="' . $id
                . '" name="' . $this->escape($field->key) . '"' . $required . '>',
            // Anything records does not know how to render becomes a plain text input rather
            // than being dropped: a field the author expects to collect must still collect.
            default => '<input type="text" id="' . $id . '" name="' . $this->escape($field->key) . '"' . $required . '>',
        };

        return '<p class="contact-form__field">' . $label . $control . '</p>';
    }

    private function renderSelect(ContactFormField $field, string $id, string $required): string
    {
        // A required `<select>` whose first option is already selected can never fail
        // validation — the browser considers it filled in. An empty placeholder first is what
        // makes `required` mean anything.
        $options = $field->required ? '<option value="" selected disabled></option>' : '';

        foreach ($field->options as $option) {
            $options .= '<option value="' . $this->escape($option) . '">' . $this->escape($option) . '</option>';
        }

        return '<select id="' . $id . '" name="' . $this->escape($field->key) . '"' . $required . '>' . $options . '</select>';
    }

    /**
     * Wording records did not author. When the issuing product supplies it, it is rendered as
     * given; the English fallback is tagged so assistive tech and crawlers are not told it is
     * in the page's language.
     */
    private function wording(?string $supplied, string $englishFallback): string
    {
        if ($supplied !== null && trim($supplied) !== '') {
            return $this->escape($supplied);
        }

        return '<span lang="en">' . $this->escape($englishFallback) . '</span>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
