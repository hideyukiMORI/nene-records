<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Validation\ValidationError;

/**
 * Parses + validates a widget create/update request body.
 *
 * @phpstan-type ParsedSettings array<string, mixed>
 */
final readonly class WidgetRequestParser
{
    /**
     * @param array<string, mixed> $settings
     * @param list<ValidationError> $errors
     */
    public function __construct(
        public string $widgetType,
        public string $region,
        public int $displayOrder,
        public ?string $title,
        public array $settings,
        public array $errors,
    ) {
    }

    /** @param array<string, mixed> $body */
    public static function parse(array $body): self
    {
        $errors = [];

        $widgetType = trim((string) ($body['widget_type'] ?? ''));
        if ($widgetType === '') {
            $errors[] = new ValidationError('widget_type', 'Widget type is required.', 'required');
        } elseif (!WidgetTypes::isValid($widgetType)) {
            $errors[] = new ValidationError('widget_type', 'Unknown widget type.', 'invalid');
        }

        $region = trim((string) ($body['region'] ?? ''));
        if ($region === '') {
            $errors[] = new ValidationError('region', 'Region is required.', 'required');
        } elseif (!WidgetRegions::isValid($region)) {
            $errors[] = new ValidationError('region', 'Region must be one of: header, sidebar, aside, footer.', 'invalid');
        }

        $displayOrder = isset($body['display_order']) && is_int($body['display_order']) ? $body['display_order'] : 0;

        $rawTitle = $body['title'] ?? null;
        $title = is_string($rawTitle) && trim($rawTitle) !== '' ? trim($rawTitle) : null;

        $rawSettings = $body['settings'] ?? null;
        /** @var array<string, mixed> $settings */
        $settings = is_array($rawSettings) ? $rawSettings : [];

        return new self($widgetType, $region, $displayOrder, $title, $settings, $errors);
    }
}
