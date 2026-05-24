<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use DateTimeImmutable;
use DateTimeZone;

final readonly class PublicFieldDisplayFormatter
{
    public static function format(string $dataType, string|int|bool|null $raw): string
    {
        if ($raw === null) {
            return '—';
        }

        return match ($dataType) {
            'bool' => $raw === true || $raw === 'true' || $raw === 1 || $raw === '1' ? 'Yes' : 'No',
            'int' => (string) $raw,
            'datetime' => self::formatDateTime((string) $raw),
            default => trim((string) $raw) === '' ? '—' : (string) $raw,
        };
    }

    private static function formatDateTime(string $iso): string
    {
        if (trim($iso) === '') {
            return '—';
        }

        $parsed = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $iso)
            ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $iso)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $iso);

        if ($parsed === false) {
            return $iso;
        }

        return $parsed->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s') . ' UTC';
    }
}
