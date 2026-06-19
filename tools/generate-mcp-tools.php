<?php

declare(strict_types=1);

/**
 * Regenerates docs/mcp/tools.json from declarative tool definitions aligned with OpenAPI.
 *
 * Usage: php tools/generate-mcp-tools.php
 */

$root = dirname(__DIR__);
$outputPath = $root . '/docs/mcp/tools.json';

/** @return array<string, mixed> */
function limitOffsetProperties(): array
{
    return [
        'limit' => [
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => 100,
            'default' => 20,
        ],
        'offset' => [
            'type' => 'integer',
            'minimum' => 0,
            'default' => 0,
        ],
    ];
}

/**
 * @param array<string, mixed> $properties
 * @param list<string> $required
 * @param bool|array<string, mixed> $additionalProperties
 * @return array<string, mixed>
 */
function readTool(
    string $name,
    string $title,
    string $description,
    string $operationId,
    string $method,
    string $path,
    array $properties,
    ?string $responseSchemaRef,
    array $required = [],
    bool|array $additionalProperties = false,
): array {
    $inputSchema = [
        'type' => 'object',
        'properties' => $properties,
    ];

    if ($required !== []) {
        $inputSchema['required'] = $required;
    }

    if ($additionalProperties === false) {
        $inputSchema['additionalProperties'] = false;
    } elseif ($additionalProperties === true) {
        $inputSchema['additionalProperties'] = true;
    } else {
        $inputSchema['additionalProperties'] = $additionalProperties;
    }

    return [
        'name' => $name,
        'title' => $title,
        'description' => $description,
        'safety' => match ($method) {
            'GET' => 'read',
            'DELETE' => 'destructive',
            default => 'write',
        },
        'source' => [
            'type' => 'openapi',
            'operationId' => $operationId,
            'method' => $method,
            'path' => $path,
        ],
        'inputSchema' => $inputSchema,
        'responseSchemaRef' => $responseSchemaRef,
    ];
}

/**
 * @param array<string, mixed> $properties
 * @param list<string> $required
 * @return array<string, mixed>
 */
function idTool(
    string $name,
    string $title,
    string $operationId,
    string $method,
    string $path,
    array $properties,
    ?string $responseSchemaRef,
    array $required,
    string $description,
): array {
    return readTool(
        $name,
        $title,
        $description,
        $operationId,
        $method,
        $path,
        $properties,
        $responseSchemaRef,
        $required,
    );
}

$idOnly = [
    'id' => [
        'type' => 'integer',
        'minimum' => 1,
        'description' => 'Resource id.',
    ],
];

// Runtime theme manifest fields (public-theme.schema.json shape). The request
// body IS the manifest; token values are sanitised server-side. See #423.
$themeManifestProps = [
    'id' => [
        'type' => 'string',
        'pattern' => '^[a-z][a-z0-9-]{1,40}$',
        'description' => 'Theme key/id. Must not collide with a built-in theme.',
    ],
    'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 80],
    'version' => ['type' => 'string', 'pattern' => '^[0-9]+\\.[0-9]+\\.[0-9]+$'],
    'supportsModes' => [
        'type' => 'array',
        'items' => ['type' => 'string', 'enum' => ['light', 'dark']],
        'description' => 'Must include both light and dark.',
    ],
    'tokens' => [
        'type' => 'object',
        'description' => 'Per-mode CSS tokens: {"light":{token:value,…},"dark":{…}}. Keys are contract tokens; values must be safe CSS (no ;{}<>, url(), @import).',
    ],
    'flags' => [
        'type' => 'object',
        'description' => 'Optional structural style flags (enumerated; e.g. feedLayout, headerLayout).',
    ],
    'fonts' => [
        'type' => 'array',
        'description' => 'Optional metadata list of {family, role, source} (source must be fontsource or system). Note: does NOT load fonts at runtime — the public site only renders families bundled in the authoring guide renderModel.fonts.available; set those via the font-* tokens.',
        'items' => ['type' => 'object', 'additionalProperties' => true],
    ],
    'assets' => [
        'type' => 'object',
        'description' => 'Optional asset slots (preview/hero/background). Each value is a media id (positive integer, from listMedia) or a safe bundle-relative path. assets.preview drives the picker thumbnail.',
        'additionalProperties' => true,
    ],
];
$themeManifestRequired = ['id', 'name', 'version', 'supportsModes', 'tokens'];

$tools = [
    readTool(
        'getHealth',
        'Health',
        'Operational health check for the NeNe Records API runtime.',
        'getHealth',
        'GET',
        '/health',
        [],
        '#/components/schemas/HealthResponse',
    ),
    readTool(
        'listEntityTypes',
        'List Entity Types',
        'List entity type schema definitions with pagination.',
        'listEntityTypes',
        'GET',
        '/api/v1/entity-types',
        limitOffsetProperties(),
        '#/components/schemas/EntityTypeListResponse',
    ),
    readTool(
        'createEntityType',
        'Create Entity Type',
        'Create a new entity type with a unique slug.',
        'createEntityType',
        'POST',
        '/api/v1/entity-types',
        [
            'name' => ['type' => 'string', 'minLength' => 1],
            'slug' => [
                'type' => 'string',
                'pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
            ],
        ],
        null,
        ['name', 'slug'],
    ),
    idTool(
        'getEntityTypeById',
        'Get Entity Type',
        'getEntityTypeById',
        'GET',
        '/api/v1/entity-types/{id}',
        $idOnly,
        '#/components/schemas/EntityTypeResponse',
        ['id'],
        'Get an entity type by id.',
    ),
    idTool(
        'updateEntityTypeById',
        'Update Entity Type',
        'updateEntityType',
        'PUT',
        '/api/v1/entity-types/{id}',
        [
            'id' => $idOnly['id'],
            'name' => ['type' => 'string', 'minLength' => 1],
            'slug' => [
                'type' => 'string',
                'pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
            ],
        ],
        '#/components/schemas/EntityTypeResponse',
        ['id', 'name', 'slug'],
        'Replace an entity type name and slug.',
    ),
    idTool(
        'deleteEntityTypeById',
        'Delete Entity Type',
        'deleteEntityType',
        'DELETE',
        '/api/v1/entity-types/{id}',
        $idOnly,
        null,
        ['id'],
        'Permanently delete an entity type.',
    ),
    readTool(
        'listEntities',
        'List Entities',
        'List entity records with optional entity_type_id, status, tag, and relation filters.',
        'listEntities',
        'GET',
        '/api/v1/entities',
        [
            ...limitOffsetProperties(),
            'entity_type_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Filter by entity type id.',
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['draft', 'published', 'archived'],
                'description' => 'Filter by publish status.',
            ],
            'tags' => [
                'type' => 'string',
                'description' => 'Comma-separated tag slugs (OR semantics).',
            ],
            'tag' => [
                'type' => 'string',
                'description' => 'Alias for a single tag slug or comma-separated slugs.',
            ],
        ],
        '#/components/schemas/EntityListResponse',
        additionalProperties: true,
    ),
    readTool(
        'listEntitiesByTag',
        'List Entities By Tag',
        'List entities matching any of the given tag slugs. Alias for listEntities with tags/tag query params.',
        'listEntities',
        'GET',
        '/api/v1/entities',
        [
            ...limitOffsetProperties(),
            'entity_type_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Optional entity type id filter.',
            ],
            'tags' => [
                'type' => 'string',
                'description' => 'Comma-separated tag slugs.',
            ],
        ],
        '#/components/schemas/EntityListResponse',
    ),
    readTool(
        'listEntitiesByRelationTarget',
        'List Entities By Relation Target',
        'List source entities whose relation field points to a target entity. Pass entity_type_id and query params named relation_{fieldKey} (e.g. relation_author=5). Multiple relation filters are ANDed.',
        'listEntities',
        'GET',
        '/api/v1/entities',
        [
            'entity_type_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Entity type id of records to list.',
            ],
            ...limitOffsetProperties(),
        ],
        '#/components/schemas/EntityListResponse',
        additionalProperties: [
            'type' => 'integer',
            'minimum' => 1,
            'description' => 'Relation filter query param. Use key relation_{fieldKey} with the target entity id as value.',
        ],
    ),
    readTool(
        'createEntity',
        'Create Entity',
        'Create a new entity record for an entity type.',
        'createEntity',
        'POST',
        '/api/v1/entities',
        [
            'entity_type_id' => [
                'type' => 'integer',
                'minimum' => 1,
            ],
            'slug' => [
                'type' => 'string',
                'minLength' => 1,
                'description' => 'URL slug (unique per entity type).',
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['draft', 'published', 'archived'],
                'description' => 'Initial publish status (default: draft).',
            ],
        ],
        null,
        ['entity_type_id'],
    ),
    idTool(
        'getEntityById',
        'Get Entity',
        'getEntityById',
        'GET',
        '/api/v1/entities/{id}',
        $idOnly,
        '#/components/schemas/EntityResponse',
        ['id'],
        'Get an entity record by id.',
    ),
    readTool(
        'getPublicRecordView',
        'Get Public Record View',
        'Aggregated bootstrap payload for a public consumer record detail page.',
        'getPublicRecordView',
        'GET',
        '/api/v1/public/entity-types/{slug}/records/{entitySlug}',
        [
            'slug' => [
                'type' => 'string',
                'minLength' => 1,
                'description' => 'Entity type slug.',
            ],
            'entitySlug' => [
                'type' => 'string',
                'minLength' => 1,
                'description' => 'Entity slug.',
            ],
        ],
        '#/components/schemas/PublicRecordViewResponse',
        ['slug', 'entitySlug'],
    ),
    idTool(
        'updateEntityById',
        'Update Entity',
        'updateEntity',
        'PUT',
        '/api/v1/entities/{id}',
        [
            'id' => $idOnly['id'],
            'entity_type_id' => [
                'type' => 'integer',
                'minimum' => 1,
            ],
            'slug' => [
                'type' => 'string',
                'minLength' => 1,
                'description' => 'URL slug (unique per entity type).',
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['draft', 'published', 'archived'],
                'description' => 'Publish status.',
            ],
            'published_at' => [
                'type' => 'string',
                'format' => 'date-time',
                'description' => 'Override published_at timestamp (ISO 8601). Auto-set on first publish if omitted.',
            ],
        ],
        '#/components/schemas/EntityResponse',
        ['id', 'entity_type_id'],
        'Update an entity record entity type id and publish status.',
    ),
    idTool(
        'deleteEntityById',
        'Delete Entity',
        'deleteEntity',
        'DELETE',
        '/api/v1/entities/{id}',
        $idOnly,
        null,
        ['id'],
        'Soft-delete an entity record.',
    ),
    readTool(
        'listEntityTags',
        'List Entity Tags',
        'List tags attached to an entity.',
        'listEntityTags',
        'GET',
        '/api/v1/entities/{entityId}/tags',
        [
            'entityId' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Entity id.',
            ],
        ],
        '#/components/schemas/EntityTagListResponse',
        ['entityId'],
    ),
    readTool(
        'attachEntityTag',
        'Attach Entity Tag',
        'Attach a tag to an entity by tag id.',
        'attachEntityTag',
        'POST',
        '/api/v1/entities/{entityId}/tags',
        [
            'entityId' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Entity id.',
            ],
            'tag_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Tag id to attach.',
            ],
        ],
        null,
        ['entityId', 'tag_id'],
    ),
    readTool(
        'detachEntityTag',
        'Detach Entity Tag',
        'Detach a tag from an entity.',
        'detachEntityTag',
        'DELETE',
        '/api/v1/entities/{entityId}/tags/{tagId}',
        [
            'entityId' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Entity id.',
            ],
            'tagId' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Tag id to detach.',
            ],
        ],
        null,
        ['entityId', 'tagId'],
    ),
    readTool(
        'listEntityRelations',
        'List Entity Relations',
        'List relation targets attached to a source entity for a given relation field key.',
        'listEntityRelations',
        'GET',
        '/api/v1/entities/{entityId}/relations',
        [
            'entityId' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Source entity id.',
            ],
            'field_key' => [
                'type' => 'string',
                'minLength' => 1,
                'description' => 'Relation field key registered on the source entity type.',
            ],
        ],
        '#/components/schemas/EntityRelationListResponse',
        ['entityId', 'field_key'],
    ),
    readTool(
        'attachEntityRelation',
        'Attach Entity Relation',
        'Attach a target entity to a source entity via a typed relation field. For cardinality=one fields, replaces the existing target.',
        'attachEntityRelation',
        'POST',
        '/api/v1/entities/{entityId}/relations',
        [
            'entityId' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Source entity id.',
            ],
            'field_key' => [
                'type' => 'string',
                'minLength' => 1,
                'description' => 'Relation field key on the source entity type.',
            ],
            'target_entity_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Target entity id to link.',
            ],
        ],
        null,
        ['entityId', 'field_key', 'target_entity_id'],
    ),
    readTool(
        'getAccessStatsByDate',
        'Access Stats By Date',
        'Return daily HTTP access counts and average duration for an inclusive date range (YYYY-MM-DD).',
        'getAccessStatsByDate',
        'GET',
        '/api/v1/analytics/access-stats',
        [
            'from' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date (inclusive), YYYY-MM-DD.',
            ],
            'to' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date (inclusive), YYYY-MM-DD. Range must not exceed 366 days.',
            ],
        ],
        '#/components/schemas/AccessStatsByDateResponse',
        ['from', 'to'],
    ),
    readTool(
        'listFieldDefs',
        'List Field Definitions',
        'List registered field definitions, optionally filtered by entity type.',
        'listFieldDefs',
        'GET',
        '/api/v1/field-defs',
        [
            ...limitOffsetProperties(),
            'entity_type_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'Filter by entity type id.',
            ],
        ],
        '#/components/schemas/FieldDefListResponse',
    ),
    readTool(
        'createFieldDef',
        'Create Field Definition',
        'Register a field key and data type for an entity type.',
        'createFieldDef',
        'POST',
        '/api/v1/field-defs',
        [
            'entity_type_id' => ['type' => 'integer', 'minimum' => 1],
            'field_key' => ['type' => 'string', 'minLength' => 1],
            'data_type' => [
                'type' => 'string',
                'enum' => ['text', 'int', 'enum', 'bool', 'datetime', 'relation'],
            ],
            'target_entity_type_id' => ['type' => 'integer', 'minimum' => 1],
            'cardinality' => ['type' => 'string', 'enum' => ['one', 'many']],
        ],
        null,
        ['entity_type_id', 'field_key', 'data_type'],
    ),
    idTool(
        'getFieldDefById',
        'Get Field Definition',
        'getFieldDefById',
        'GET',
        '/api/v1/field-defs/{id}',
        $idOnly,
        '#/components/schemas/FieldDefResponse',
        ['id'],
        'Get a field definition by id.',
    ),
    idTool(
        'updateFieldDefById',
        'Update Field Definition',
        'updateFieldDef',
        'PUT',
        '/api/v1/field-defs/{id}',
        [
            'id' => $idOnly['id'],
            'entity_type_id' => ['type' => 'integer', 'minimum' => 1],
            'field_key' => ['type' => 'string', 'minLength' => 1],
            'data_type' => [
                'type' => 'string',
                'enum' => ['text', 'int', 'enum', 'bool', 'datetime', 'relation'],
            ],
            'target_entity_type_id' => ['type' => 'integer', 'minimum' => 1],
            'cardinality' => ['type' => 'string', 'enum' => ['one', 'many']],
        ],
        '#/components/schemas/FieldDefResponse',
        ['id', 'entity_type_id', 'field_key', 'data_type'],
        'Replace a field definition.',
    ),
    idTool(
        'deleteFieldDefById',
        'Delete Field Definition',
        'deleteFieldDef',
        'DELETE',
        '/api/v1/field-defs/{id}',
        $idOnly,
        null,
        ['id'],
        'Soft-delete a field definition.',
    ),
    readTool(
        'listTags',
        'List Tags',
        'List tag definitions with pagination.',
        'listTags',
        'GET',
        '/api/v1/tags',
        limitOffsetProperties(),
        '#/components/schemas/TagListResponse',
    ),
    readTool(
        'createTag',
        'Create Tag',
        'Create a new tag with a unique slug.',
        'createTag',
        'POST',
        '/api/v1/tags',
        [
            'name' => ['type' => 'string', 'minLength' => 1],
            'slug' => [
                'type' => 'string',
                'pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
            ],
        ],
        null,
        ['name', 'slug'],
    ),
    idTool(
        'getTagById',
        'Get Tag',
        'getTagById',
        'GET',
        '/api/v1/tags/{id}',
        $idOnly,
        '#/components/schemas/TagResponse',
        ['id'],
        'Get a tag by id.',
    ),
    idTool(
        'updateTagById',
        'Update Tag',
        'updateTag',
        'PUT',
        '/api/v1/tags/{id}',
        [
            'id' => $idOnly['id'],
            'name' => ['type' => 'string', 'minLength' => 1],
            'slug' => [
                'type' => 'string',
                'pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
            ],
        ],
        '#/components/schemas/TagResponse',
        ['id', 'name', 'slug'],
        'Replace a tag name and slug.',
    ),
    idTool(
        'deleteTagById',
        'Delete Tag',
        'deleteTag',
        'DELETE',
        '/api/v1/tags/{id}',
        $idOnly,
        null,
        ['id'],
        'Permanently delete a tag.',
    ),
    readTool(
        'listSettings',
        'List Settings',
        'List all site settings with effective values for admin use.',
        'listSettings',
        'GET',
        '/api/v1/settings',
        [],
        '#/components/schemas/SettingListResponse',
    ),
    readTool(
        'listPublicSettings',
        'List Public Settings',
        'List public site settings for consumer pages.',
        'listPublicSettings',
        'GET',
        '/api/v1/public/settings',
        [],
        '#/components/schemas/PublicSettingListResponse',
    ),
    readTool(
        'updateSettingByKey',
        'Update Setting',
        'Update a site setting value by key.',
        'updateSettingByKey',
        'PUT',
        '/api/v1/settings/{key}',
        [
            'key' => [
                'type' => 'string',
                'pattern' => '^[a-z][a-z0-9_]*$',
                'description' => 'Setting key.',
            ],
            'value' => ['type' => 'string'],
        ],
        '#/components/schemas/SettingValueResponse',
        ['key', 'value'],
    ),
    readTool(
        'listSettingRevisions',
        'List Setting Revisions',
        'List paginated revision history for a setting key.',
        'listSettingRevisions',
        'GET',
        '/api/v1/settings/{key}/revisions',
        [
            'key' => [
                'type' => 'string',
                'pattern' => '^[a-z][a-z0-9_]*$',
                'description' => 'Setting key.',
            ],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
            'offset' => ['type' => 'integer', 'minimum' => 0],
        ],
        '#/components/schemas/SettingRevisionListResponse',
        ['key'],
    ),
    readTool(
        'listMedia',
        'List Media',
        'List uploaded media items (newest first). Use this to find the media id for a theme manifest assets.preview / hero / background slot.',
        'listMedia',
        'GET',
        '/api/v1/media',
        [],
        '#/components/schemas/MediaListResponse',
    ),
    readTool(
        'getThemeAuthoringGuide',
        'Theme Authoring Guide',
        'Read this FIRST before creating or updating a runtime theme. Returns the manifest contract (required tokens, flag enums, token value rules, reserved ids), recipes, common mistakes and a minimal valid example manifest. Derived from the server validator so it matches exactly what createTheme/updateTheme enforce.',
        'getThemeAuthoringGuide',
        'GET',
        '/api/v1/themes/authoring-guide',
        [],
        '#/components/schemas/ThemeAuthoringGuideResponse',
    ),
    readTool(
        'listThemes',
        'List Runtime Themes',
        'List all runtime (data-driven) public-site themes for the organization.',
        'listThemes',
        'GET',
        '/api/v1/themes',
        [],
        '#/components/schemas/ThemeListResponse',
    ),
    readTool(
        'createTheme',
        'Create Runtime Theme',
        'Register a new runtime public-site theme. The input is the theme manifest (tokens + flags). The server validates structure and sanitises token values before storing; unsafe CSS and reserved ids are rejected.',
        'createTheme',
        'POST',
        '/api/v1/themes',
        $themeManifestProps,
        null,
        $themeManifestRequired,
        true,
    ),
    // Non-mutating dry-run despite POST → safety 'read' (overrides the method default).
    array_merge(
        readTool(
            'previewTheme',
            'Preview Runtime Theme',
            'Compute a no-browser preview of a theme manifest: returns the tokens that would render (safe-filtered), the ones dropped, and WCAG contrast for key pairs (light/dark). Non-persistent — use to self-correct contrast/quality before createTheme/updateTheme.',
            'previewTheme',
            'POST',
            '/api/v1/themes/preview',
            $themeManifestProps,
            '#/components/schemas/ThemePreviewResponse',
            $themeManifestRequired,
            true,
        ),
        ['safety' => 'read'],
    ),
    readTool(
        'getTheme',
        'Get Runtime Theme',
        'Get a single runtime theme by its key.',
        'getTheme',
        'GET',
        '/api/v1/themes/{key}',
        [
            'key' => [
                'type' => 'string',
                'pattern' => '^[a-z][a-z0-9-]{1,40}$',
                'description' => 'Theme key.',
            ],
        ],
        '#/components/schemas/ThemeResponse',
        ['key'],
    ),
    readTool(
        'updateTheme',
        'Update Runtime Theme',
        'Replace an existing runtime theme manifest. The manifest id must match the key in the path.',
        'updateTheme',
        'PUT',
        '/api/v1/themes/{key}',
        array_merge(
            [
                'key' => [
                    'type' => 'string',
                    'pattern' => '^[a-z][a-z0-9-]{1,40}$',
                    'description' => 'Theme key (must equal the manifest id).',
                ],
            ],
            $themeManifestProps,
        ),
        '#/components/schemas/ThemeResponse',
        array_merge(['key'], $themeManifestRequired),
        true,
    ),
    readTool(
        'deleteTheme',
        'Delete Runtime Theme',
        'Permanently delete a runtime theme by its key.',
        'deleteTheme',
        'DELETE',
        '/api/v1/themes/{key}',
        [
            'key' => [
                'type' => 'string',
                'pattern' => '^[a-z][a-z0-9-]{1,40}$',
                'description' => 'Theme key.',
            ],
        ],
        null,
        ['key'],
    ),
];

/** @return list<array<string, mixed>> */
function fieldTools(string $kind): array
{
    if (!in_array($kind, ['text', 'int', 'enum', 'bool', 'datetime'], true)) {
        throw new InvalidArgumentException('Unsupported field kind: ' . $kind);
    }
    $segment = match ($kind) {
        'text' => 'text-fields',
        'int' => 'int-fields',
        'enum' => 'enum-fields',
        'bool' => 'bool-fields',
        'datetime' => 'datetime-fields',
    };

    $title = match ($kind) {
        'text' => 'Text Field',
        'int' => 'Int Field',
        'enum' => 'Enum Field',
        'bool' => 'Bool Field',
        'datetime' => 'DateTime Field',
    };

    $response = '#/components/schemas/' . match ($kind) {
        'text' => 'TextFieldResponse',
        'int' => 'IntFieldResponse',
        'enum' => 'EnumFieldResponse',
        'bool' => 'BoolFieldResponse',
        'datetime' => 'DateTimeFieldResponse',
    };

    $listResponse = '#/components/schemas/' . match ($kind) {
        'text' => 'TextFieldListResponse',
        'int' => 'IntFieldListResponse',
        'enum' => 'EnumFieldListResponse',
        'bool' => 'BoolFieldListResponse',
        'datetime' => 'DateTimeFieldListResponse',
    };

    $valueSchema = match ($kind) {
        'text', 'enum', 'datetime' => ['type' => 'string'],
        'int' => ['type' => 'integer'],
        'bool' => ['type' => 'boolean'],
    };

    $prefix = match ($kind) {
        'text' => 'Text',
        'int' => 'Int',
        'enum' => 'Enum',
        'bool' => 'Bool',
        'datetime' => 'DateTime',
    };

    $listOp = 'list' . $prefix . 'Fields';
    $createOp = 'create' . $prefix . 'Field';
    $getOp = 'get' . $prefix . 'FieldById';
    $updateOp = 'update' . $prefix . 'Field';
    $deleteOp = 'delete' . $prefix . 'Field';

    return [
        readTool(
            $listOp,
            "List {$title}s",
            "List {$kind} field values with optional entity or entity type filters.",
            $listOp,
            'GET',
            "/api/v1/{$segment}",
            [
                ...limitOffsetProperties(),
                'entity_id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Filter by entity id.',
                ],
                'entity_type_id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Filter by entity type id.',
                ],
            ],
            $listResponse,
        ),
        readTool(
            $createOp,
            "Create {$title}",
            "Create a {$kind} field value on an entity.",
            $createOp,
            'POST',
            "/api/v1/{$segment}",
            [
                'entity_id' => ['type' => 'integer', 'minimum' => 1],
                'field_key' => ['type' => 'string', 'minLength' => 1],
                'value' => $valueSchema,
            ],
            null,
            ['entity_id', 'field_key', 'value'],
        ),
        idTool(
            'get' . $prefix . 'FieldById',
            "Get {$title}",
            $getOp,
            'GET',
            "/api/v1/{$segment}/{id}",
            ['id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Resource id.']],
            $response,
            ['id'],
            "Get a {$kind} field value by id.",
        ),
        idTool(
            'update' . $prefix . 'FieldById',
            "Update {$title}",
            $updateOp,
            'PUT',
            "/api/v1/{$segment}/{id}",
            [
                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Resource id.'],
                'field_key' => ['type' => 'string', 'minLength' => 1],
                'value' => $valueSchema,
            ],
            $response,
            ['id', 'field_key', 'value'],
            "Replace a {$kind} field value.",
        ),
        idTool(
            'delete' . $prefix . 'FieldById',
            "Delete {$title}",
            $deleteOp,
            'DELETE',
            "/api/v1/{$segment}/{id}",
            ['id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Resource id.']],
            null,
            ['id'],
            "Soft-delete a {$kind} field value.",
        ),
    ];
}

foreach (['text', 'int', 'enum', 'bool', 'datetime'] as $kind) {
    array_push($tools, ...fieldTools($kind));
}

// Auth
$tools[] = readTool(
    'login',
    'Login',
    'Authenticate with email/password and obtain an API bearer token.',
    'login',
    'POST',
    '/api/v1/auth/login',
    [
        'email' => ['type' => 'string', 'format' => 'email', 'minLength' => 1],
        'password' => ['type' => 'string', 'minLength' => 1],
    ],
    '#/components/schemas/LoginResponse',
    ['email', 'password'],
);

$catalog = [
    'version' => 1,
    'source' => 'docs/openapi/openapi.yaml',
    'tools' => $tools,
];

$json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
file_put_contents($outputPath, $json);

echo sprintf("Wrote %d MCP tools to %s\n", count($tools), $outputPath);
