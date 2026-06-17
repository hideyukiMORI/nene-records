<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final class SettingValueValidator
{
    /** @var list<string> */
    private const SUPPORTED_DATA_TYPES = ['text', 'markdown', 'bool', 'url', 'media'];

    public static function normalize(string $dataType, string $value): string
    {
        self::assertSupportedDataType($dataType);

        return match ($dataType) {
            'bool' => self::normalizeBool($value),
            default => $value,
        };
    }

    public static function validate(string $dataType, string $value): void
    {
        self::assertSupportedDataType($dataType);

        match ($dataType) {
            'bool' => self::validateBool($value),
            'url' => self::validateUrl($value),
            'media' => self::validateMedia($value),
            'text', 'markdown' => null,
            default => self::assertSupportedDataType($dataType),
        };
    }

    private static function assertSupportedDataType(string $dataType): void
    {
        if (!in_array($dataType, self::SUPPORTED_DATA_TYPES, true)) {
            throw new SettingValueInvalidException("Unsupported setting data type: {$dataType}.");
        }
    }

    private static function normalizeBool(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true' => 'true',
            '0', 'false' => 'false',
            default => throw new SettingValueInvalidException('Boolean setting value must be true or false.'),
        };
    }

    private static function validateBool(string $value): void
    {
        self::normalizeBool($value);
    }

    private static function validateUrl(string $value): void
    {
        if ($value === '') {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new SettingValueInvalidException('URL setting value must be a valid URL.');
        }
    }

    /**
     * A media setting stores a media id (resolved to a URL in the public API).
     * Empty clears it; otherwise it must be a positive integer.
     */
    private static function validateMedia(string $value): void
    {
        if ($value === '') {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0) {
            throw new SettingValueInvalidException('Media setting value must be a media id.');
        }
    }
}
