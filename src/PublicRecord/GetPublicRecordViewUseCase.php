<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\BoolField\BoolField;
use NeNeRecords\BoolField\BoolFieldRepositoryInterface;
use NeNeRecords\DateTimeField\DateTimeField;
use NeNeRecords\DateTimeField\DateTimeFieldRepositoryInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityRelation\EntityRelationRepositoryInterface;
use NeNeRecords\EntityRelation\ListEntityRelationItem;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\EnumField\EnumField;
use NeNeRecords\EnumField\EnumFieldRepositoryInterface;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\IntField\IntField;
use NeNeRecords\IntField\IntFieldRepositoryInterface;
use NeNeRecords\Media\MediaDerivativeUrl;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use NeNeRecords\Setting\SettingEntry;
use NeNeRecords\Setting\SettingHttpMapper;
use NeNeRecords\TextField\TextField;
use NeNeRecords\TextField\TextFieldRepositoryInterface;

final readonly class GetPublicRecordViewUseCase implements GetPublicRecordViewUseCaseInterface
{
    private const FIELD_DEF_LIMIT = 20;
    private const FIELD_VALUE_LIMIT = 100;
    private const ENTITY_TYPE_LIMIT = 100;

    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private EntityRepositoryInterface $entities,
        private FieldDefRepositoryInterface $fieldDefs,
        private TextFieldRepositoryInterface $textFields,
        private IntFieldRepositoryInterface $intFields,
        private EnumFieldRepositoryInterface $enumFields,
        private BoolFieldRepositoryInterface $boolFields,
        private DateTimeFieldRepositoryInterface $dateTimeFields,
        private EntityRelationRepositoryInterface $entityRelations,
        private ListPublicSettingsUseCaseInterface $publicSettings,
        private PublicRecordHierarchyBuilder $hierarchyBuilder,
    ) {
    }

    public function execute(GetPublicRecordViewInput $input): GetPublicRecordViewOutput
    {
        $entityType = $this->entityTypes->findBySlug($input->entityTypeSlug);

        if ($entityType === null || $entityType->id === null) {
            throw new PublicEntityTypeNotFoundException($input->entityTypeSlug);
        }

        // Resolve by numeric id (id-style permalinks) or by slug. findBySlug already
        // scopes to the type; for findById we verify the type explicitly below.
        $entity = $input->entityId !== null
            ? $this->entities->findById($input->entityId)
            : ($input->entitySlug !== null
                ? $this->entities->findBySlug($input->entitySlug, $entityType->id)
                : null);

        if (
            $entity === null
            || $entity->id === null
            || $entity->entityTypeId !== $entityType->id
            || $entity->isDeleted
            || $entity->status !== EntityStatus::Published
        ) {
            throw new PublicRecordNotFoundException(
                $input->entityTypeSlug,
                $input->entitySlug ?? (string) ($input->entityId ?? 'unknown'),
            );
        }

        $entityTypeId = $entityType->id;
        $entityId = $entity->id;

        $fieldDefRows = $this->fieldDefs->findAll($entityTypeId, self::FIELD_DEF_LIMIT, 0);
        $dataTypes = array_values(array_unique(array_map(
            static fn (FieldDef $fieldDef): string => $fieldDef->dataType,
            $fieldDefRows,
        )));

        $textFieldRows = (in_array('text', $dataTypes, true) || in_array('markdown', $dataTypes, true) || in_array('html', $dataTypes, true) || in_array('bundle', $dataTypes, true) || in_array('image', $dataTypes, true) || in_array('file', $dataTypes, true))
            ? $this->resolveLocaleRows($this->textFields->findByEntityId($entityId, self::FIELD_VALUE_LIMIT, 0), $input->locale)
            : [];
        $intFieldRows = in_array('int', $dataTypes, true)
            ? $this->intFields->findByEntityId($entityId, self::FIELD_VALUE_LIMIT, 0)
            : [];
        $enumFieldRows = in_array('enum', $dataTypes, true)
            ? $this->enumFields->findByEntityId($entityId, self::FIELD_VALUE_LIMIT, 0)
            : [];
        $boolFieldRows = in_array('bool', $dataTypes, true)
            ? $this->boolFields->findByEntityId($entityId, self::FIELD_VALUE_LIMIT, 0)
            : [];
        $dateTimeFieldRows = in_array('datetime', $dataTypes, true)
            ? $this->dateTimeFields->findByEntityId($entityId, self::FIELD_VALUE_LIMIT, 0)
            : [];

        $allEntityTypes = $this->entityTypes->findAll(self::ENTITY_TYPE_LIMIT, 0);
        $entityTypeSlugById = [];

        foreach ($allEntityTypes as $listedType) {
            if ($listedType->id !== null) {
                $entityTypeSlugById[$listedType->id] = $listedType->slug;
            }
        }

        $relationTextFieldsByEntityTypeId = [];
        $targetEntityTypeIds = [];

        foreach ($fieldDefRows as $fieldDef) {
            if ($fieldDef->dataType === 'relation' && $fieldDef->targetEntityTypeId !== null) {
                $targetEntityTypeIds[$fieldDef->targetEntityTypeId] = true;
            }
        }

        foreach (array_keys($targetEntityTypeIds) as $targetEntityTypeId) {
            $relationTextFieldsByEntityTypeId[$targetEntityTypeId] = $this->textFields->findByEntityTypeId(
                $targetEntityTypeId,
                self::FIELD_VALUE_LIMIT,
                0,
            );
        }

        $allRelations = $this->entityRelations->findByEntityId($entityId);
        $relationsByFieldKey = [];

        foreach ($allRelations as $relation) {
            $relationsByFieldKey[$relation->fieldKey][] = $relation;
        }

        $displayFields = $this->buildDisplayFields(
            $fieldDefRows,
            $textFieldRows,
            $intFieldRows,
            $enumFieldRows,
            $boolFieldRows,
            $dateTimeFieldRows,
            $entityId,
            $entityTypeSlugById,
            $relationTextFieldsByEntityTypeId,
            $relationsByFieldKey,
        );

        // The SEO title override (entity.meta_title, e.g. imported from Yoast) wins
        // for <title>/og:title; otherwise fall back to the record's title field.
        $metaTitle = trim($entity->metaTitle ?? '');
        $pageTitle = $metaTitle !== '' ? $metaTitle : $this->resolvePageTitle($textFieldRows, $entityId);

        $bootstrap = PublicRecordViewHttpMapper::toBootstrap(
            entityTypeSlug: $input->entityTypeSlug,
            entityType: $entityType,
            entity: $entity,
            allEntityTypes: $allEntityTypes,
            fieldDefRows: $fieldDefRows,
            textFieldRows: $textFieldRows,
            intFieldRows: $intFieldRows,
            enumFieldRows: $enumFieldRows,
            boolFieldRows: $boolFieldRows,
            dateTimeFieldRows: $dateTimeFieldRows,
            entityId: $entityId,
            entityTypeId: $entityTypeId,
            relationQueries: $this->buildRelationQueries($relationsByFieldKey),
            relationTextFieldRowsByEntityTypeId: $relationTextFieldsByEntityTypeId,
        );

        $publicSettingsOutput = $this->publicSettings->execute();
        $bootstrap['publicSettings'] = [
            'items' => array_map(
                static fn (SettingEntry $entry) => SettingHttpMapper::entryToPublicArray($entry),
                $publicSettingsOutput->items,
            ),
        ];

        // Derived chapter navigation (小説家になろう/AO3 hub-and-spoke): present only
        // when this record is one chapter of a multi-chapter work. Hidden from the
        // ordinary field display; resolved like the canonical permalink.
        $chapterNav = ChapterNavBuilder::build(
            $entityType->permalinkPattern,
            $input->entityTypeSlug,
            $this->findTextValue($textFieldRows, 'series'),
            $this->findIntValue($intFieldRows, 'chapter_no'),
            $this->findIntValue($intFieldRows, 'chapter_total'),
        );
        $bootstrap['chapterNav'] = ChapterNavBuilder::toBootstrapArray($chapterNav);

        $canonicalPath = PublicPermalinkResolver::canonicalPath(
            $entity->permalink,
            $entityType->permalinkPattern,
            $input->entityTypeSlug,
            $entity->slug,
            $entityId,
            $entity->publishedAt,
        );

        // Permalink-path-derived breadcrumb + child pages (#651 PR2). Empty for
        // ordinary `/{type}/{slug}` records; populated for custom-permalink pages.
        $hierarchy = $this->hierarchyBuilder->build($entity->permalink, $canonicalPath, $pageTitle);
        $bootstrap['hierarchy'] = $hierarchy->toArray();

        $ogImagePath = $this->resolveOgImagePath($displayFields);

        return new GetPublicRecordViewOutput(
            entityTypeSlug: $input->entityTypeSlug,
            entityTypeName: $entityType->name,
            entityId: $entityId,
            entitySlug: $entity->slug ?? (string) $entityId,
            pageTitle: $pageTitle,
            metaDescription: $entity->metaDescription ?? '',
            canonicalPath: $canonicalPath,
            ogImagePath: $ogImagePath,
            publishedAtIso: $entity->publishedAt?->format(\DateTimeInterface::ATOM),
            updatedAtIso: $entity->updatedAt?->format(\DateTimeInterface::ATOM),
            bootstrap: $bootstrap,
            displayFields: $displayFields,
            chapterNav: $chapterNav,
            breadcrumbs: $hierarchy->breadcrumbs,
            childPages: $hierarchy->childPages,
        );
    }

    /**
     * Pick the first image field whose value resolves to a media derivative,
     * for use as the og:image / twitter:image social card. Null if none.
     *
     * @param list<PublicRecordViewDisplayField> $displayFields
     */
    private function resolveOgImagePath(array $displayFields): ?string
    {
        foreach ($displayFields as $field) {
            if ($field->dataType !== 'image') {
                continue;
            }

            $candidate = MediaDerivativeUrl::forPreset($field->displayValue, 'og');

            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param list<FieldDef> $fieldDefRows
     * @param list<TextField> $textFieldRows
     * @param list<IntField> $intFieldRows
     * @param list<EnumField> $enumFieldRows
     * @param list<BoolField> $boolFieldRows
     * @param list<DateTimeField> $dateTimeFieldRows
     * @param array<int, string> $entityTypeSlugById
     * @param array<int, list<TextField>> $relationTextFieldsByEntityTypeId
     * @param array<string, list<ListEntityRelationItem>> $relationsByFieldKey
     * @return list<PublicRecordViewDisplayField>
     */
    private function buildDisplayFields(
        array $fieldDefRows,
        array $textFieldRows,
        array $intFieldRows,
        array $enumFieldRows,
        array $boolFieldRows,
        array $dateTimeFieldRows,
        int $entityId,
        array $entityTypeSlugById,
        array $relationTextFieldsByEntityTypeId,
        array $relationsByFieldKey,
    ): array {
        $displayFields = [];

        foreach ($fieldDefRows as $fieldDef) {
            // Reserved chapter-nav metadata is surfaced as the derived chapter
            // navigation, never as an ordinary field row.
            if (in_array($fieldDef->fieldKey, ChapterNavBuilder::FIELD_KEYS, true)) {
                continue;
            }

            if ($fieldDef->dataType === 'relation') {
                $targetEntityTypeId = $fieldDef->targetEntityTypeId;

                if ($targetEntityTypeId === null) {
                    continue;
                }

                $targetSlug = $entityTypeSlugById[$targetEntityTypeId] ?? (string) $targetEntityTypeId;
                $labelFields = $relationTextFieldsByEntityTypeId[$targetEntityTypeId] ?? [];
                $relations = $relationsByFieldKey[$fieldDef->fieldKey] ?? [];
                $links = [];

                foreach ($relations as $relation) {
                    $links[] = [
                        'label' => $this->resolveRecordLabel($relation->targetEntityId, $labelFields),
                        'href' => '/view/' . $targetSlug . '/' . $relation->targetEntityId,
                    ];
                }

                $displayFields[] = new PublicRecordViewDisplayField(
                    fieldKey: $fieldDef->fieldKey,
                    dataType: 'relation',
                    displayValue: $links === [] ? '—' : implode(', ', array_column($links, 'label')),
                    relationLinks: $links,
                );

                continue;
            }

            $raw = match ($fieldDef->dataType) {
                'text', 'markdown', 'html', 'bundle', 'image', 'file' => $this->findTextValue($textFieldRows, $fieldDef->fieldKey),
                'int' => $this->findIntValue($intFieldRows, $fieldDef->fieldKey),
                'enum' => $this->findEnumValue($enumFieldRows, $fieldDef->fieldKey),
                'bool' => $this->findBoolValue($boolFieldRows, $fieldDef->fieldKey),
                'datetime' => $this->findDateTimeValue($dateTimeFieldRows, $fieldDef->fieldKey),
                default => null,
            };

            $displayFields[] = new PublicRecordViewDisplayField(
                fieldKey: $fieldDef->fieldKey,
                dataType: $fieldDef->dataType,
                displayValue: PublicFieldDisplayFormatter::format($fieldDef->dataType, $raw),
            );
        }

        return $displayFields;
    }

    /**
     * @param array<string, list<ListEntityRelationItem>> $relationsByFieldKey
     * @return list<array{fieldKey: string, items: list<array{field_key: string, target_entity_id: int}>}>
     */
    private function buildRelationQueries(array $relationsByFieldKey): array
    {
        $queries = [];

        foreach ($relationsByFieldKey as $fieldKey => $relations) {
            $items = array_map(
                static fn ($relation) => [
                    'field_key' => $relation->fieldKey,
                    'target_entity_id' => $relation->targetEntityId,
                ],
                $relations,
            );

            $queries[] = [
                'fieldKey' => $fieldKey,
                'items' => $items,
            ];
        }

        return $queries;
    }

    /** @param list<TextField> $textFieldRows */
    private function resolvePageTitle(array $textFieldRows, int $entityId): string
    {
        return $this->resolveRecordLabel($entityId, $textFieldRows);
    }

    /** @param list<TextField> $textFieldRows */
    private function resolveRecordLabel(int $entityId, array $textFieldRows): string
    {
        foreach ($textFieldRows as $textField) {
            if ($textField->entityId === $entityId && $textField->fieldKey === 'title' && trim($textField->value) !== '') {
                return $textField->value;
            }
        }

        foreach ($textFieldRows as $textField) {
            if ($textField->entityId === $entityId && trim($textField->value) !== '') {
                return $textField->value;
            }
        }

        return 'Record #' . $entityId;
    }

    /**
     * Negotiate text-field rows down to one per field key for the requested
     * locale (#540): prefer the requested locale, then the locale-agnostic (null)
     * row, then the first available. Resolving here keeps the SSR display fields,
     * the SPA bootstrap and the page title all consistent for the served locale.
     *
     * @param list<TextField> $rows
     * @return list<TextField>
     */
    private function resolveLocaleRows(array $rows, ?string $locale): array
    {
        $byKey = [];
        $order = [];

        foreach ($rows as $row) {
            if (!array_key_exists($row->fieldKey, $byKey)) {
                $byKey[$row->fieldKey] = [];
                $order[] = $row->fieldKey;
            }
            $byKey[$row->fieldKey][] = $row;
        }

        $resolved = [];

        foreach ($order as $fieldKey) {
            $candidates = $byKey[$fieldKey];
            $pick = null;

            if ($locale !== null) {
                $pick = $this->firstWhere($candidates, static fn (TextField $r): bool => $r->locale === $locale);
            }

            $pick ??= $this->firstWhere($candidates, static fn (TextField $r): bool => $r->locale === null);
            $resolved[] = $pick ?? $candidates[0];
        }

        return $resolved;
    }

    /**
     * @param list<TextField> $rows
     * @param callable(TextField): bool $predicate
     */
    private function firstWhere(array $rows, callable $predicate): ?TextField
    {
        foreach ($rows as $row) {
            if ($predicate($row)) {
                return $row;
            }
        }

        return null;
    }

    /** @param list<TextField> $rows */
    private function findTextValue(array $rows, string $fieldKey): ?string
    {
        foreach ($rows as $row) {
            if ($row->fieldKey === $fieldKey) {
                return $row->value;
            }
        }

        return null;
    }

    /** @param list<IntField> $rows */
    private function findIntValue(array $rows, string $fieldKey): ?int
    {
        foreach ($rows as $row) {
            if ($row->fieldKey === $fieldKey) {
                return $row->value;
            }
        }

        return null;
    }

    /** @param list<EnumField> $rows */
    private function findEnumValue(array $rows, string $fieldKey): ?string
    {
        foreach ($rows as $row) {
            if ($row->fieldKey === $fieldKey) {
                return $row->value;
            }
        }

        return null;
    }

    /** @param list<BoolField> $rows */
    private function findBoolValue(array $rows, string $fieldKey): ?bool
    {
        foreach ($rows as $row) {
            if ($row->fieldKey === $fieldKey) {
                return $row->value;
            }
        }

        return null;
    }

    /** @param list<DateTimeField> $rows */
    private function findDateTimeValue(array $rows, string $fieldKey): ?string
    {
        foreach ($rows as $row) {
            if ($row->fieldKey === $fieldKey) {
                return $row->value;
            }
        }

        return null;
    }
}
