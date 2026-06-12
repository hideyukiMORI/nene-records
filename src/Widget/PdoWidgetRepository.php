<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoWidgetRepository implements WidgetRepositoryInterface
{
    private const COLUMNS = 'id, widget_type, region, display_order, title, settings, created_at, updated_at';

    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private RequestScopedHolder $orgId,
    ) {
    }

    /** @return list<Widget> */
    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM widgets WHERE organization_id = ? ORDER BY region ASC, display_order ASC, id ASC',
            [$this->orgId->get()],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function findById(int $id): ?Widget
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM widgets WHERE id = ? AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function save(Widget $widget): int
    {
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO widgets (organization_id, widget_type, region, display_order, title, settings, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->orgId->get(),
                $widget->widgetType,
                $widget->region,
                $widget->displayOrder,
                $widget->title,
                $this->encodeSettings($widget->settings),
                $now,
                $now,
            ],
        );

        return $this->query->lastInsertId();
    }

    public function update(Widget $widget): void
    {
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'UPDATE widgets SET widget_type = ?, region = ?, display_order = ?, title = ?, settings = ?, updated_at = ?
             WHERE id = ? AND organization_id = ?',
            [
                $widget->widgetType,
                $widget->region,
                $widget->displayOrder,
                $widget->title,
                $this->encodeSettings($widget->settings),
                $now,
                $widget->id,
                $this->orgId->get(),
            ],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM widgets WHERE id = ? AND organization_id = ?', [$id, $this->orgId->get()]);
    }

    /** @param array<string, mixed> $settings */
    private function encodeSettings(array $settings): ?string
    {
        if ($settings === []) {
            return null;
        }

        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Widget
    {
        $settings = [];
        if (isset($row['settings']) && is_string($row['settings']) && $row['settings'] !== '') {
            $decoded = json_decode($row['settings'], true);
            if (is_array($decoded)) {
                /** @var array<string, mixed> $decoded */
                $settings = $decoded;
            }
        }

        return new Widget(
            id: (int) $row['id'],
            widgetType: (string) $row['widget_type'],
            region: (string) $row['region'],
            displayOrder: (int) $row['display_order'],
            title: ($row['title'] ?? null) !== null && $row['title'] !== '' ? (string) $row['title'] : null,
            settings: $settings,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
