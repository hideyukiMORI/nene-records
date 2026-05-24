<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

final readonly class AccessStatsDateRangeParser
{
    private const MAX_RANGE_DAYS = 366;

    /**
     * @param array<string, mixed> $query
     */
    public static function parse(array $query): AccessStatsDateRange
    {
        $fromRaw = trim((string) ($query['from'] ?? ''));
        $toRaw = trim((string) ($query['to'] ?? ''));

        $errors = [];

        if ($fromRaw === '') {
            $errors[] = new ValidationError('from', 'Start date is required.', 'required');
        }

        if ($toRaw === '') {
            $errors[] = new ValidationError('to', 'End date is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $from = self::parseDate('from', $fromRaw);
        $to = self::parseDate('to', $toRaw);

        if ($from > $to) {
            throw new ValidationException([
                new ValidationError('from', 'Start date must be on or before end date.', 'invalid'),
            ]);
        }

        $rangeDays = (int) $from->diff($to)->days + 1;

        if ($rangeDays > self::MAX_RANGE_DAYS) {
            throw new ValidationException([
                new ValidationError('to', sprintf('Date range must not exceed %d days.', self::MAX_RANGE_DAYS), 'invalid'),
            ]);
        }

        return new AccessStatsDateRange($from, $to);
    }

    private static function parseDate(string $field, string $value): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));

        if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
            throw new ValidationException([
                new ValidationError($field, 'Date must use YYYY-MM-DD format.', 'format'),
            ]);
        }

        return $parsed;
    }
}
