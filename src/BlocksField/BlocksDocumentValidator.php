<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

/**
 * Server-side validator for a blocks document — the value of a `blocks` field
 * (#486). This is the trust boundary: the admin editor and MCP write tools are
 * convenience, but only this guarantees a stored document is a well-formed list
 * of curated, typed blocks. Mirrors the hand-written style of
 * {@see \NeNeRecords\Theme\ThemeManifestValidator}; the canonical shape lives in
 * docs/blocks/blocks.schema.json (frontend Ajv source).
 *
 * A document is `[{ id, type, data }]`. Each `type` must be whitelisted
 * ({@see BlockTypes}) and its `data` must match that type's shape.
 */
final class BlocksDocumentValidator
{
    public const MAX_BLOCKS = 200;
    private const MAX_ID_LEN = 64;
    private const MAX_TITLE_LEN = 200;
    private const MAX_TEXT_LEN = 50000;

    /** @var list<string> */
    private const CALLOUT_KINDS = ['info', 'warn', 'ok', 'danger'];

    /**
     * @throws ValidationException when the document is malformed or a block is invalid
     */
    public function validate(string $json): void
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new ValidationException([
                new ValidationError('value', 'Blocks document must be a JSON array of blocks.', 'invalid'),
            ]);
        }

        if (count($decoded) > self::MAX_BLOCKS) {
            throw new ValidationException([
                new ValidationError('value', 'A blocks document may contain at most ' . self::MAX_BLOCKS . ' blocks.', 'invalid'),
            ]);
        }

        $errors = [];

        foreach ($decoded as $index => $block) {
            $this->validateBlock($index, $block, $errors);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private function validateBlock(int $index, mixed $block, array &$errors): void
    {
        $path = "value[{$index}]";

        if (!is_array($block)) {
            $errors[] = new ValidationError($path, 'Each block must be an object.', 'invalid');

            return;
        }

        $id = $block['id'] ?? null;
        if (!is_string($id) || $id === '' || strlen($id) > self::MAX_ID_LEN) {
            $errors[] = new ValidationError("{$path}.id", 'Block id must be a non-empty string (max ' . self::MAX_ID_LEN . ' chars).', 'invalid');
        }

        $type = $block['type'] ?? null;
        if (!is_string($type) || !BlockTypes::isValid($type)) {
            $errors[] = new ValidationError("{$path}.type", 'Block type must be one of: ' . implode(', ', BlockTypes::all()) . '.', 'invalid');

            return;
        }

        $data = $block['data'] ?? null;
        if (!is_array($data)) {
            $errors[] = new ValidationError("{$path}.data", 'Block data must be an object.', 'invalid');

            return;
        }

        match ($type) {
            'text' => $this->validateTextData($path, $data, $errors),
            'callout' => $this->validateCalloutData($path, $data, $errors),
            default => null,
        };
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateTextData(string $path, array $data, array &$errors): void
    {
        $markdown = $data['markdown'] ?? null;
        if (!is_string($markdown) || $markdown === '' || strlen($markdown) > self::MAX_TEXT_LEN) {
            $errors[] = new ValidationError("{$path}.data.markdown", 'Text block requires non-empty markdown (max ' . self::MAX_TEXT_LEN . ' chars).', 'invalid');
        }
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateCalloutData(string $path, array $data, array &$errors): void
    {
        $kind = $data['kind'] ?? null;
        if (!is_string($kind) || !in_array($kind, self::CALLOUT_KINDS, true)) {
            $errors[] = new ValidationError("{$path}.data.kind", 'Callout kind must be one of: ' . implode(', ', self::CALLOUT_KINDS) . '.', 'invalid');
        }

        $body = $data['body'] ?? null;
        if (!is_string($body) || $body === '' || strlen($body) > self::MAX_TEXT_LEN) {
            $errors[] = new ValidationError("{$path}.data.body", 'Callout block requires a non-empty body (max ' . self::MAX_TEXT_LEN . ' chars).', 'invalid');
        }

        $title = $data['title'] ?? null;
        if ($title !== null && (!is_string($title) || strlen($title) > self::MAX_TITLE_LEN)) {
            $errors[] = new ValidationError("{$path}.data.title", 'Callout title must be a string (max ' . self::MAX_TITLE_LEN . ' chars).', 'invalid');
        }
    }
}
