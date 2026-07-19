<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Seeds the built-in setting definitions for a newly created organization (#711).
 *
 * The catalog below is the single canonical list of built-in settings, mirroring what
 * the per-org def migrations installed for orgs that existed when they ran. Without
 * org-creation seeding, an org born later (self-serve signup, the Tier A installer,
 * superadmin CRUD) had an almost empty settings surface — no site name / logo defs
 * and no active_theme row to persist a theme choice.
 *
 * ⚠️ Rule for NEW settings: add them BOTH here (new orgs) AND as a per-org backfill
 * migration (existing orgs) — the migration and this catalog intentionally duplicate
 * values because migrations must stay self-contained snapshots.
 *
 * SQL lives here (Pdo* class) to keep it out of the UseCase layer. Idempotent: it
 * checks for an existing (org, key) row before inserting.
 */
final readonly class PdoDefaultSettingDefsSeeder implements DefaultSettingDefsSeederInterface
{
    /**
     * @var list<array{setting_key: string, data_type: string, default_value: string, is_public: int, label: string}>
     */
    private const SETTING_DEFS = [
        ['setting_key' => 'site_name', 'data_type' => 'text', 'default_value' => 'NeNe Records', 'is_public' => 1, 'label' => 'Site name'],
        ['setting_key' => 'tagline', 'data_type' => 'text', 'default_value' => 'API-first flexible entity platform', 'is_public' => 1, 'label' => 'Tagline'],
        ['setting_key' => 'default_meta_description', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Default meta description'],
        ['setting_key' => 'footer_markdown', 'data_type' => 'markdown', 'default_value' => '', 'is_public' => 1, 'label' => 'Footer'],
        ['setting_key' => 'active_theme', 'data_type' => 'text', 'default_value' => 'consumer', 'is_public' => 1, 'label' => 'Public site theme'],
        ['setting_key' => 'theme_overrides', 'data_type' => 'text', 'default_value' => '{}', 'is_public' => 1, 'label' => 'Theme customizations'],
        ['setting_key' => 'logo_media_id', 'data_type' => 'media', 'default_value' => '', 'is_public' => 1, 'label' => 'Logo'],
        ['setting_key' => 'default_og_image', 'data_type' => 'media', 'default_value' => '', 'is_public' => 1, 'label' => 'Default social image (og:image)'],
        ['setting_key' => 'copyright_text', 'data_type' => 'text', 'default_value' => '© {year} {site}', 'is_public' => 1, 'label' => 'Copyright'],
        ['setting_key' => 'layout_config', 'data_type' => 'text', 'default_value' => '{"home":{"columns":2,"mainPos":"left","swap":false},"record":{"columns":3,"mainPos":"left","swap":false}}', 'is_public' => 1, 'label' => 'Layout'],
        ['setting_key' => 'excerpt_source', 'data_type' => 'text', 'default_value' => 'auto', 'is_public' => 0, 'label' => 'Excerpt source (auto / body / meta)'],
        ['setting_key' => 'excerpt_length', 'data_type' => 'text', 'default_value' => '160', 'is_public' => 0, 'label' => 'Excerpt length (characters)'],
        ['setting_key' => 'header_config', 'data_type' => 'text', 'default_value' => '{"topbar":{"enabled":false,"phone":"","email":"","infoText":""},"cta":{"enabled":false,"label":"","url":""}}', 'is_public' => 1, 'label' => 'Header'],
        ['setting_key' => 'footer_config', 'data_type' => 'text', 'default_value' => '{"social":[],"legalLinks":[],"showPoweredBy":true}', 'is_public' => 1, 'label' => 'Footer content'],
        ['setting_key' => 'home_hero', 'data_type' => 'text', 'default_value' => '[]', 'is_public' => 1, 'label' => 'Home hero'],
        // Trusted-embed allowlist (#802): JSON array of self-owned https origins a
        // public page may embed. Read server-side into the CSP; empty by default.
        ['setting_key' => 'embed_allowlist', 'data_type' => 'text', 'default_value' => '[]', 'is_public' => 1, 'label' => 'Trusted embed origins'],
        ['setting_key' => 'analytics_gtm_id', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Google Tag Manager container ID'],
        ['setting_key' => 'analytics_ga4_id', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Google Analytics 4 measurement ID'],
        ['setting_key' => 'analytics_consent_default', 'data_type' => 'text', 'default_value' => 'denied', 'is_public' => 1, 'label' => 'Analytics consent default (denied/granted)'],
        ['setting_key' => 'front_page', 'data_type' => 'text', 'default_value' => '', 'is_public' => 1, 'label' => 'Front page'],
        ['setting_key' => 'record_page_config', 'data_type' => 'text', 'default_value' => '{"comments":true,"related":true}', 'is_public' => 1, 'label' => 'Record page display'],
        // Per-org maintenance mode (#813): when 'true', anonymous visitors get a 503
        // maintenance page on the public surface; logged-in staff pass through.
        // Operational flag — not exposed via the public settings API (is_public 0).
        ['setting_key' => 'maintenance_mode', 'data_type' => 'bool', 'default_value' => 'false', 'is_public' => 0, 'label' => 'Maintenance mode'],
    ];

    public function __construct(private DatabaseQueryExecutorInterface $query)
    {
    }

    public function seed(int $organizationId): void
    {
        foreach (self::SETTING_DEFS as $def) {
            $existing = $this->query->fetchOne(
                'SELECT id FROM setting_defs WHERE organization_id = ? AND setting_key = ?',
                [$organizationId, $def['setting_key']],
            );

            if ($existing !== null) {
                continue;
            }

            $this->query->execute(
                'INSERT INTO setting_defs (organization_id, setting_key, data_type, default_value, is_public, label, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $organizationId,
                    $def['setting_key'],
                    $def['data_type'],
                    $def['default_value'],
                    $def['is_public'],
                    $def['label'],
                ],
            );
        }
    }
}
