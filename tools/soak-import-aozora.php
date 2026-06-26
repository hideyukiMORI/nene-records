<?php

declare(strict_types=1);

/**
 * SOAK-test importer — bulk-import 青空文庫 (Aozora Bunko) public-domain works
 * into a NeNe Records tenant as real content at volume.
 *
 * Two backends:
 *   --mode=http    Paced HTTP client against the public API (default). Honours a
 *                  per-request delay so the GLOBAL rate limit (~120 req/60s per
 *                  proxy IP) is never tripped. Good for local DRY-RUN.
 *   --mode=direct  Boots the app container (RuntimeContainerFactory) and calls the
 *                  use-cases in-process — NO HTTP, NO rate limit, NO auth round-trip.
 *                  This is the PROD-scale path; run it INSIDE the records-app container.
 *
 * Source: enumerates an author's works via the GitHub contents API of
 * aozorabunko/aozorabunko (cards/<person>/files), then fetches each work's text
 * from the aozorahack/aozorabunko_text mirror (pre-unzipped Shift_JIS .txt),
 * converts SJIS-win→UTF-8 and strips ruby 《》 / base-markers ｜ / annotations ［＃…］.
 *
 * Idempotent: each work gets the slug  aozora-<person>-<workId>  and is skipped if
 * that slug already exists for the target entity type.
 *
 * Examples:
 *   # DRY-RUN, parse only, no writes:
 *   php tools/soak-import-aozora.php --dry --limit=8
 *
 *   # Local import via paced HTTP (default base = http://localhost:18082):
 *   php tools/soak-import-aozora.php --mode=http --limit=8 \
 *       --email=admin@aozora.local --password=soaktest1234
 *
 *   # PROD-scale import inside the container (no rate limit), all Sōseki works:
 *   docker compose exec -T app php tools/soak-import-aozora.php \
 *       --mode=direct --org=aozora --person=000148 --limit=0
 *
 * Flags:
 *   --mode=http|direct     backend (default http)
 *   --base=URL             API base for http mode (default http://localhost:18082)
 *   --email= --password=   admin login for http mode (or env NENE_INSTALL_ADMIN_*)
 *   --org=slug             tenant slug for direct mode (default env ORG_SLUG or "aozora")
 *   --person=000148        Aozora person id to import (default 000148 = 夏目漱石)
 *   --work=752,789         restrict to these workIds (comma-separated; default all)
 *   --limit=N              max works (0 = all; default 8)
 *   --type-slug=work       target entity-type slug (default "work")
 *   --type-name=作品        target entity-type display name (default "作品")
 *   --body-type=markdown   field type for the body (markdown|html|text; default markdown)
 *   --delay=0.7            seconds between HTTP API calls in http mode (default 0.7 ⇒ ~1.4 req/s)
 *   --dry                  fetch + parse + report only; create nothing
 *   --chapters             split works with >= 2 detected 見出し chapters into a 目次
 *                          (index) record + one record per chapter (series/chapter_no/
 *                          chapter_total + slug permalinks); single-record otherwise
 */

// Autoload is only required for --mode=direct. Load it when present so the script
// still runs from a host that has no vendor/ when only doing http mode.
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

use NeNeRecords\ApplicationServiceProvider;
use NeNeRecords\Entity\CreateEntityInput;
use NeNeRecords\Entity\CreateEntityUseCaseInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Entity\UpdateEntityInput;
use NeNeRecords\Entity\UpdateEntityUseCaseInterface;
use NeNeRecords\EntityType\CreateEntityTypeInput;
use NeNeRecords\EntityType\CreateEntityTypeUseCaseInterface;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\EntityType\UpdateEntityTypeInput;
use NeNeRecords\EntityType\UpdateEntityTypeUseCaseInterface;
use NeNeRecords\FieldDef\CreateFieldDefInput;
use NeNeRecords\FieldDef\CreateFieldDefUseCaseInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\IntField\CreateIntFieldInput;
use NeNeRecords\IntField\CreateIntFieldUseCaseInterface;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\TextField\CreateTextFieldInput;
use NeNeRecords\TextField\CreateTextFieldUseCaseInterface;

/* ============================== Aozora parsing ============================== */

final class AozoraParser
{
    /**
     * A 大/中/小見出し heading annotation. Aozora marks chapter titles like
     * `［＃５字下げ］一［＃「一」は中見出し］` — the reliable signal is this
     * annotation, NOT the bare word 見出し (which also occurs in prose).
     */
    private const HEADING_RE = '/［＃「[^」]*」は(?:大|中|小)見出し］/u';

    /** Bare numeric chapter labels (CJK or arabic) → rendered as 第{label}章. */
    private const NUMERAL_RE = '/^[0-9０-９一二三四五六七八九十百千〇零]+$/u';

    /**
     * Convert a raw Shift_JIS Aozora .txt into clean (title, author, body, stats).
     *
     * @return array{title:string,author:string,body:string,paragraphs:int,chars:int}
     */
    public static function parse(string $rawShiftJis): array
    {
        $region = self::region($rawShiftJis);
        $body = self::joinParas($region['lines']);

        return [
            'title' => $region['title'],
            'author' => $region['author'],
            'body' => $body,
            'paragraphs' => $body === '' ? 0 : count(explode("\n\n", $body)),
            'chars' => mb_strlen($body, 'UTF-8'),
        ];
    }

    /**
     * Split a work into chapters at 大/中/小見出し headings. The text before the
     * first heading is the work intro (for the 目次 record). Each chapter keeps
     * its visible heading label (e.g. "一", "十一") and its own body.
     *
     * @return array{title:string,author:string,intro:string,chapters:list<array{label:string,body:string,chars:int}>}
     */
    public static function parseChapters(string $rawShiftJis): array
    {
        $region = self::region($rawShiftJis);
        $lines = $region['lines'];

        $headings = [];
        foreach ($lines as $i => $line) {
            if (preg_match(self::HEADING_RE, $line) === 1) {
                $headings[] = $i;
            }
        }

        $firstHeading = $headings === [] ? count($lines) : $headings[0];
        $intro = self::joinParas(array_slice($lines, 0, $firstHeading));

        $chapters = [];
        $count = count($headings);
        for ($k = 0; $k < $count; $k++) {
            $start = $headings[$k];
            $end = $k + 1 < $count ? $headings[$k + 1] : count($lines);
            $label = trim(self::stripInline($lines[$start]));
            $body = self::joinParas(array_slice($lines, $start + 1, $end - $start - 1));
            $chapters[] = [
                'label' => $label,
                'body' => $body,
                'chars' => mb_strlen($body, 'UTF-8'),
            ];
        }

        return [
            'title' => $region['title'],
            'author' => $region['author'],
            'intro' => $intro,
            'chapters' => $chapters,
        ];
    }

    /** True when a heading label is a bare numeral that reads well as 第{label}章. */
    public static function isNumeralLabel(string $label): bool
    {
        return $label !== '' && preg_match(self::NUMERAL_RE, $label) === 1;
    }

    /**
     * Extract title/author and the body region (raw lines, annotations intact so
     * chapter headings remain detectable). Shared by parse() and parseChapters().
     *
     * @return array{title:string,author:string,lines:list<string>}
     */
    private static function region(string $rawShiftJis): array
    {
        $utf8 = mb_convert_encoding($rawShiftJis, 'UTF-8', 'SJIS-win');
        $utf8 = str_replace(["\r\n", "\r"], "\n", $utf8);
        $lines = explode("\n", $utf8);

        // Title / author = first two non-empty lines.
        $nonEmpty = [];
        foreach ($lines as $i => $l) {
            if (trim($l) !== '') {
                $nonEmpty[] = $i;
                if (count($nonEmpty) >= 2) {
                    break;
                }
            }
        }
        $title = self::stripInline(trim($lines[$nonEmpty[0] ?? 0] ?? ''));
        $author = self::stripInline(trim($lines[$nonEmpty[1] ?? 0] ?? ''));

        // Header: the 凡例 block is bracketed by two separator lines of repeated
        // dashes (real files use ASCII '-' ×55; tolerate fullwidth variants too).
        $sep = [];
        foreach ($lines as $i => $l) {
            if (preg_match('/^[\-\x{FF0D}\x{30FC}\x{2500}\x{2015}]{2,}$/u', trim($l)) === 1) {
                $sep[] = $i;
            }
        }
        $bodyStart = count($sep) >= 2 ? $sep[1] + 1 : ($nonEmpty[1] ?? 0) + 1;

        // Footer starts at the first 底本： line (optionally indented w/ FW space).
        $bodyEnd = count($lines);
        for ($i = $bodyStart; $i < count($lines); $i++) {
            if (preg_match('/^[\s\x{3000}]*底本[：:]/u', $lines[$i]) === 1) {
                $bodyEnd = $i;
                break;
            }
        }

        return [
            'title' => $title,
            'author' => $author,
            'lines' => array_values(array_slice($lines, $bodyStart, $bodyEnd - $bodyStart)),
        ];
    }

    /**
     * Strip annotations + ruby per line, drop blanks, join paragraphs with a
     * blank line (markdown paragraph break).
     *
     * @param list<string> $lines
     */
    private static function joinParas(array $lines): string
    {
        $paras = [];
        foreach ($lines as $l) {
            $c = trim(self::stripInline($l));
            if ($c !== '') {
                $paras[] = $c;
            }
        }

        return implode("\n\n", $paras);
    }

    /** Strip annotations ［＃…］ / ※［＃…］, ruby readings 《…》, and ruby markers ｜. */
    private static function stripInline(string $s): string
    {
        return preg_replace(
            ['/※?［＃[^］]*］/u', '/《[^》]*》/u', '/｜/u'],
            '',
            $s,
        ) ?? $s;
    }
}

/* ============================== Aozora source ============================== */

final class AozoraSource
{
    private const GH_API = 'https://api.github.com/repos/aozorabunko/aozorabunko/contents/cards/%s/files?per_page=1000';
    private const TEXT_MIRROR = 'https://raw.githubusercontent.com/aozorahack/aozorabunko_text/master/cards/%s/files/%s/%s.txt';
    private const ZIP_RAW = 'https://raw.githubusercontent.com/aozorabunko/aozorabunko/master/cards/%s/files/%s.zip';

    /**
     * Enumerate a person's text-file basenames, one per workId (prefers _ruby_).
     *
     * @return list<array{workId:string,base:string}>
     */
    public static function listWorks(string $person): array
    {
        $json = self::httpGet(sprintf(self::GH_API, $person));
        /** @var list<array{name?:string,type?:string}> $entries */
        $entries = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $byWork = [];
        foreach ($entries as $e) {
            $name = (string) ($e['name'] ?? '');
            if (preg_match('/^(\d+)_.*\.zip$/', $name, $m) !== 1 && preg_match('/^(\d+)\.zip$/', $name, $m) !== 1) {
                continue;
            }
            $workId = $m[1];
            $base = substr($name, 0, -4); // drop .zip
            // Prefer the ruby variant when a work has several text files.
            if (!isset($byWork[$workId]) || (str_contains($base, '_ruby_') && !str_contains($byWork[$workId], '_ruby_'))) {
                $byWork[$workId] = $base;
            }
        }

        ksort($byWork, SORT_NATURAL);
        $out = [];
        foreach ($byWork as $workId => $base) {
            $out[] = ['workId' => (string) $workId, 'base' => $base];
        }

        return $out;
    }

    /** Fetch a work's raw Shift_JIS bytes (aozorahack mirror, fallback to zip+unzip). */
    public static function fetchRawText(string $person, string $base): string
    {
        $url = sprintf(self::TEXT_MIRROR, $person, $base, $base);
        $raw = self::httpGet($url, false);
        if ($raw !== null && $raw !== '') {
            return $raw;
        }

        // Fallback: download the zip and extract via the shell `unzip` (the PHP zip
        // extension is not built into the records-app image).
        $zipUrl = sprintf(self::ZIP_RAW, $person, $base);
        $zipBytes = self::httpGet($zipUrl, false);
        if ($zipBytes === null || $zipBytes === '') {
            throw new RuntimeException("Could not fetch text for {$base} (mirror + zip both failed).");
        }
        $tmp = tempnam(sys_get_temp_dir(), 'aozora_') ?: throw new RuntimeException('tempnam failed');
        file_put_contents($tmp, $zipBytes);
        $out = shell_exec('unzip -p ' . escapeshellarg($tmp) . ' 2>/dev/null');
        @unlink($tmp);
        if (!is_string($out) || $out === '') {
            throw new RuntimeException("unzip fallback produced no text for {$base}.");
        }

        return $out;
    }

    /** GET with a UA header. When $throw is false, returns null on failure instead of throwing. */
    private static function httpGet(string $url, bool $throw = true): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'nene-records-soak-importer',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($body) || $status >= 400) {
            if ($throw) {
                throw new RuntimeException("GET {$url} failed (HTTP {$status}).");
            }

            return null;
        }

        return $body;
    }
}

/* ============================== Backends ============================== */

interface ImportBackend
{
    public function ensureType(string $slug, string $name): int;

    public function ensureFieldDef(int $typeId, string $fieldKey, string $dataType): void;

    public function findEntityIdBySlug(string $slug, int $typeId): ?int;

    public function createEntity(int $typeId, string $slug): int;

    public function setTextField(int $entityId, string $fieldKey, string $value): void;

    public function setIntField(int $entityId, string $fieldKey, int $value): void;

    /** Ensure the entity type uses the given permalink pattern (idempotent). */
    public function ensurePermalinkPattern(int $typeId, string $name, string $slug, string $pattern): void;

    public function publish(int $entityId, int $typeId, string $slug, string $metaTitle, string $metaDescription): void;

    public function apiCallCount(): int;
}

/** Paced HTTP backend — keeps the global rate limit happy via a min inter-request delay. */
final class HttpBackend implements ImportBackend
{
    private string $token = '';
    private float $lastCall = 0.0;
    private int $calls = 0;
    /** @var array<int, array<string,int>> typeId => slug => entityId */
    private array $slugCache = [];

    public function __construct(
        private readonly string $base,
        private readonly float $delay,
    ) {
    }

    public function login(string $email, string $password): array
    {
        $res = $this->call('POST', '/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        if ($res['status'] !== 200 || !is_string($res['body']['token'] ?? null)) {
            throw new RuntimeException('Login failed: ' . json_encode($res['body']));
        }
        $this->token = $res['body']['token'];

        return ['org_id' => $res['body']['org_id'] ?? null, 'role' => $res['body']['role'] ?? null];
    }

    public function ensureType(string $slug, string $name): int
    {
        $list = $this->ok('GET', '/api/v1/entity-types?limit=100&offset=0');
        foreach ($list['items'] ?? [] as $it) {
            if (($it['slug'] ?? null) === $slug) {
                return (int) $it['id'];
            }
        }
        $created = $this->ok('POST', '/api/v1/entity-types', ['name' => $name, 'slug' => $slug, 'is_pinned' => true]);

        return (int) $created['id'];
    }

    public function ensureFieldDef(int $typeId, string $fieldKey, string $dataType): void
    {
        $list = $this->ok('GET', "/api/v1/field-defs?entity_type_id={$typeId}&limit=100&offset=0");
        foreach ($list['items'] ?? [] as $it) {
            if (($it['field_key'] ?? null) === $fieldKey) {
                return;
            }
        }
        $this->ok('POST', '/api/v1/field-defs', [
            'entity_type_id' => $typeId,
            'field_key' => $fieldKey,
            'data_type' => $dataType,
        ]);
    }

    public function findEntityIdBySlug(string $slug, int $typeId): ?int
    {
        if (!isset($this->slugCache[$typeId])) {
            $this->slugCache[$typeId] = [];
            $offset = 0;
            do {
                $page = $this->ok('GET', "/api/v1/entities?entity_type_id={$typeId}&limit=100&offset={$offset}");
                $items = $page['items'] ?? [];
                foreach ($items as $it) {
                    if (is_string($it['slug'] ?? null)) {
                        $this->slugCache[$typeId][$it['slug']] = (int) $it['id'];
                    }
                }
                $offset += 100;
            } while (count($items) === 100);
        }

        return $this->slugCache[$typeId][$slug] ?? null;
    }

    public function createEntity(int $typeId, string $slug): int
    {
        $res = $this->call('POST', '/api/v1/entities', [
            'entity_type_id' => $typeId,
            'slug' => $slug,
            'status' => 'draft',
        ]);
        if ($res['status'] === 409) {
            // Slug already exists (idempotent race) — look it up.
            unset($this->slugCache[$typeId]);
            $id = $this->findEntityIdBySlug($slug, $typeId);
            if ($id !== null) {
                return $id;
            }
        }
        if ($res['status'] >= 400) {
            throw new RuntimeException("createEntity {$slug}: HTTP {$res['status']} " . json_encode($res['body']));
        }
        $id = (int) $res['body']['id'];
        $this->slugCache[$typeId][$slug] = $id;

        return $id;
    }

    public function setTextField(int $entityId, string $fieldKey, string $value): void
    {
        $this->ok('POST', '/api/v1/text-fields', [
            'entity_id' => $entityId,
            'field_key' => $fieldKey,
            'value' => $value,
        ]);
    }

    public function setIntField(int $entityId, string $fieldKey, int $value): void
    {
        $this->ok('POST', '/api/v1/int-fields', [
            'entity_id' => $entityId,
            'field_key' => $fieldKey,
            'value' => $value,
        ]);
    }

    public function ensurePermalinkPattern(int $typeId, string $name, string $slug, string $pattern): void
    {
        $list = $this->ok('GET', '/api/v1/entity-types?limit=100&offset=0');
        $current = null;
        foreach ($list['items'] ?? [] as $it) {
            if ((int) ($it['id'] ?? 0) === $typeId) {
                $current = $it['permalink_pattern'] ?? null;
                break;
            }
        }
        if ($current === $pattern) {
            return;
        }
        $this->ok('PUT', "/api/v1/entity-types/{$typeId}", [
            'name' => $name,
            'slug' => $slug,
            'is_pinned' => true,
            'permalink_pattern' => $pattern,
        ]);
    }

    public function publish(int $entityId, int $typeId, string $slug, string $metaTitle, string $metaDescription): void
    {
        $this->ok('PUT', "/api/v1/entities/{$entityId}", [
            'entity_type_id' => $typeId,
            'slug' => $slug,
            'status' => 'published',
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ]);
    }

    public function apiCallCount(): int
    {
        return $this->calls;
    }

    /** @param array<string,mixed>|null $body @return array{status:int,body:array<string,mixed>} */
    private function call(string $method, string $path, ?array $body = null): array
    {
        // Pace: enforce a minimum gap between API calls.
        $elapsed = microtime(true) - $this->lastCall;
        if ($elapsed < $this->delay) {
            usleep((int) (($this->delay - $elapsed) * 1_000_000));
        }
        $this->lastCall = microtime(true);
        $this->calls++;

        $ch = curl_init($this->base . $path);
        if ($ch === false) {
            throw new RuntimeException('curl_init failed');
        }
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if (!is_string($raw)) {
            throw new RuntimeException("{$method} {$path} transport error: {$err}");
        }
        /** @var array<string,mixed> $decoded */
        $decoded = $raw === '' ? [] : (json_decode($raw, true) ?? ['_raw' => $raw]);

        return ['status' => $status, 'body' => $decoded];
    }

    /** Call and throw on >= 400. @param array<string,mixed>|null $body @return array<string,mixed> */
    private function ok(string $method, string $path, ?array $body = null): array
    {
        $res = $this->call($method, $path, $body);
        if ($res['status'] >= 400) {
            throw new RuntimeException("{$method} {$path}: HTTP {$res['status']} " . json_encode($res['body']));
        }

        return $res['body'];
    }
}

/** In-process backend — boots the app container and calls use-cases directly. */
final class DirectBackend implements ImportBackend
{
    private CreateEntityTypeUseCaseInterface $createType;
    private UpdateEntityTypeUseCaseInterface $updateType;
    private CreateFieldDefUseCaseInterface $createFieldDef;
    private CreateEntityUseCaseInterface $createEntity;
    private CreateTextFieldUseCaseInterface $createText;
    private CreateIntFieldUseCaseInterface $createInt;
    private UpdateEntityUseCaseInterface $updateEntity;
    private EntityTypeRepositoryInterface $types;
    private EntityRepositoryInterface $entities;
    private FieldDefRepositoryInterface $fieldDefs;

    public function __construct(string $orgSlug)
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

        $orgs = $container->get(OrganizationRepositoryInterface::class);
        $org = $orgs->findBySlug($orgSlug);
        if ($org === null || $org->id === null) {
            throw new RuntimeException("Organization '{$orgSlug}' not found — run app:install first.");
        }
        $holder = $container->get(ApplicationServiceProvider::ORG_ID_HOLDER);
        $holder->set($org->id);

        $this->createType = $container->get(CreateEntityTypeUseCaseInterface::class);
        $this->updateType = $container->get(UpdateEntityTypeUseCaseInterface::class);
        $this->createFieldDef = $container->get(CreateFieldDefUseCaseInterface::class);
        $this->createEntity = $container->get(CreateEntityUseCaseInterface::class);
        $this->createText = $container->get(CreateTextFieldUseCaseInterface::class);
        $this->createInt = $container->get(CreateIntFieldUseCaseInterface::class);
        $this->updateEntity = $container->get(UpdateEntityUseCaseInterface::class);
        $this->types = $container->get(EntityTypeRepositoryInterface::class);
        $this->entities = $container->get(EntityRepositoryInterface::class);
        $this->fieldDefs = $container->get(FieldDefRepositoryInterface::class);
    }

    public function ensureType(string $slug, string $name): int
    {
        $existing = $this->types->findBySlug($slug);
        if ($existing !== null && $existing->id !== null) {
            return $existing->id;
        }

        return $this->createType->execute(new CreateEntityTypeInput($name, $slug, true))->id;
    }

    public function ensureFieldDef(int $typeId, string $fieldKey, string $dataType): void
    {
        foreach ($this->fieldDefs->findAll($typeId, 500, 0) as $d) {
            if ($d->fieldKey === $fieldKey) {
                return;
            }
        }
        $this->createFieldDef->execute(new CreateFieldDefInput($typeId, $fieldKey, $dataType));
    }

    public function findEntityIdBySlug(string $slug, int $typeId): ?int
    {
        return $this->entities->findBySlug($slug, $typeId)?->id;
    }

    public function createEntity(int $typeId, string $slug): int
    {
        return $this->createEntity->execute(new CreateEntityInput($typeId, $slug, EntityStatus::Draft))->id;
    }

    public function setTextField(int $entityId, string $fieldKey, string $value): void
    {
        $this->createText->execute(new CreateTextFieldInput($entityId, $fieldKey, $value));
    }

    public function setIntField(int $entityId, string $fieldKey, int $value): void
    {
        $this->createInt->execute(new CreateIntFieldInput($entityId, $fieldKey, $value));
    }

    public function ensurePermalinkPattern(int $typeId, string $name, string $slug, string $pattern): void
    {
        $type = $this->types->findById($typeId);
        if ($type === null || $type->permalinkPattern === $pattern) {
            return;
        }
        $this->updateType->execute(new UpdateEntityTypeInput(
            id: $typeId,
            name: $name,
            slug: $slug,
            isPinned: true,
            permalinkPattern: $pattern,
        ));
    }

    public function publish(int $entityId, int $typeId, string $slug, string $metaTitle, string $metaDescription): void
    {
        $this->updateEntity->execute(new UpdateEntityInput(
            id: $entityId,
            entityTypeId: $typeId,
            slug: $slug,
            status: EntityStatus::Published,
            publishedAt: null,
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
        ));
    }

    public function apiCallCount(): int
    {
        return 0;
    }
}

/* ============================== Importer ============================== */

final class Importer
{
    public function __construct(
        private readonly ImportBackend $backend,
        private readonly string $person,
        private readonly string $typeSlug,
        private readonly string $typeName,
        private readonly string $bodyType,
        private readonly bool $dry,
        private readonly bool $chapters = false,
        /** @var list<string> Restrict to these workIds (empty = all). */
        private readonly array $workFilter = [],
    ) {
    }

    public function run(int $limit): void
    {
        $works = AozoraSource::listWorks($this->person);
        fprintf(STDERR, "Found %d works for person %s.\n", count($works), $this->person);
        if ($this->workFilter !== []) {
            $works = array_values(array_filter(
                $works,
                fn (array $w): bool => in_array($w['workId'], $this->workFilter, true),
            ));
            fprintf(STDERR, "Filtered to %d work(s): %s\n", count($works), implode(',', $this->workFilter));
        }
        if ($limit > 0) {
            $works = array_slice($works, 0, $limit);
        }

        $typeId = 0;
        if (!$this->dry) {
            $typeId = $this->backend->ensureType($this->typeSlug, $this->typeName);
            $this->backend->ensureFieldDef($typeId, 'title', 'text');
            $this->backend->ensureFieldDef($typeId, 'author', 'text');
            $this->backend->ensureFieldDef($typeId, 'body', $this->bodyType);
            if ($this->chapters) {
                // Chapter records carry the work slug + position; a slug permalink
                // lets the derived chapter nav resolve sibling URLs with no fetch.
                $this->backend->ensureFieldDef($typeId, 'series', 'text');
                $this->backend->ensureFieldDef($typeId, 'chapter_no', 'int');
                $this->backend->ensureFieldDef($typeId, 'chapter_total', 'int');
                $this->backend->ensurePermalinkPattern($typeId, $this->typeName, $this->typeSlug, '/{type}/{slug}');
            }
            fprintf(STDERR, "Entity type '%s' (#%d) ready with fields title/author/body%s.\n", $this->typeSlug, $typeId, $this->chapters ? '/series/chapter_no/chapter_total (slug permalinks)' : '');
        }

        $created = $skipped = $failed = $records = 0;
        $t0 = microtime(true);
        printf("%-14s %-26s %8s %8s %8s %8s  %s\n", 'workId', 'title', 'chars', 'fetchms', 'writems', 'status', 'slug');

        foreach ($works as $w) {
            $slug = sprintf('aozora-%s-%s', $this->person, $w['workId']);
            $title = '';
            $chars = 0;
            $fetchMs = $writeMs = 0.0;
            $status = 'ok';
            try {
                // Idempotent at the work level: once the work slug (single record,
                // or the 目次/index record in chapter mode) exists, skip the work.
                if (!$this->dry && $this->backend->findEntityIdBySlug($slug, $typeId) !== null) {
                    $skipped++;
                    $status = 'skip';
                    printf("%-14s %-26s %8s %8s %8s %8s  %s\n", $w['workId'], '(exists)', '-', '-', '-', $status, $slug);
                    continue;
                }

                $fs = microtime(true);
                $raw = AozoraSource::fetchRawText($this->person, $w['base']);
                $fetchMs = (microtime(true) - $fs) * 1000;

                // Chapter split: a work with >= 2 detected 見出し headings becomes a
                // 目次 record + one record per chapter; otherwise single-record.
                if ($this->chapters) {
                    $pc = AozoraParser::parseChapters($raw);
                    if (count($pc['chapters']) >= 2) {
                        $title = $pc['title'];
                        $n = count($pc['chapters']);
                        $chars = (int) array_sum(array_map(static fn (array $c): int => $c['chars'], $pc['chapters']));

                        if ($this->dry) {
                            printf("%-14s %-26s %8d %8.0f %8s %8s  %s\n", $w['workId'], self::clip($title), $chars, $fetchMs, '-', 'dry/' . $n . 'ch', $slug);
                            $created++;
                            $records += $n + 1;
                            continue;
                        }

                        $ws = microtime(true);
                        $records += $this->writeChapteredWork($typeId, $slug, $pc);
                        $writeMs = (microtime(true) - $ws) * 1000;
                        $created++;
                        printf("%-14s %-26s %8d %8.0f %8.0f %8s  %s\n", $w['workId'], self::clip($title), $chars, $fetchMs, $writeMs, 'split/' . $n . 'ch', $slug);
                        continue;
                    }
                }

                $parsed = AozoraParser::parse($raw);
                $title = $parsed['title'];
                $chars = $parsed['chars'];

                if ($this->dry) {
                    printf("%-14s %-26s %8d %8.0f %8s %8s  %s\n", $w['workId'], self::clip($title), $chars, $fetchMs, '-', 'dry', $slug);
                    $created++;
                    $records++;
                    continue;
                }

                $ws = microtime(true);
                $entityId = $this->backend->createEntity($typeId, $slug);
                $this->backend->setTextField($entityId, 'title', $parsed['title']);
                $this->backend->setTextField($entityId, 'author', $parsed['author']);
                $this->backend->setTextField($entityId, 'body', $parsed['body']);
                $excerpt = mb_substr(str_replace("\n", ' ', $parsed['body']), 0, 140, 'UTF-8');
                $this->backend->publish($entityId, $typeId, $slug, $parsed['title'], $excerpt);
                $writeMs = (microtime(true) - $ws) * 1000;

                $created++;
                $records++;
                printf("%-14s %-26s %8d %8.0f %8.0f %8s  %s\n", $w['workId'], self::clip($title), $chars, $fetchMs, $writeMs, $status, $slug);
            } catch (Throwable $e) {
                $failed++;
                $status = 'FAIL';
                printf("%-14s %-26s %8s %8s %8s %8s  %s\n", $w['workId'], self::clip($title ?: $w['base']), $chars ?: '-', '-', '-', $status, $slug);
                fprintf(STDERR, "  ! %s: %s\n", $slug, $e->getMessage());
            }
        }

        $elapsed = microtime(true) - $t0;
        printf(
            "\nDone in %.1fs — works created=%d skipped=%d failed=%d ; records written=%d ; API calls=%d (%.2f req/s)\n",
            $elapsed,
            $created,
            $skipped,
            $failed,
            $records,
            $this->backend->apiCallCount(),
            $this->backend->apiCallCount() > 0 ? $this->backend->apiCallCount() / max($elapsed, 0.001) : 0.0,
        );
    }

    /**
     * Write a multi-chapter work: a 目次 (index) record listing every chapter,
     * plus one published record per chapter carrying series/chapter_no/chapter_total.
     * Idempotent per record.
     *
     * @param array{title:string,author:string,intro:string,chapters:list<array{label:string,body:string,chars:int}>} $pc
     * @return int number of records actually written (0..N+1)
     */
    private function writeChapteredWork(int $typeId, string $workSlug, array $pc): int
    {
        $workTitle = $pc['title'];
        $author = $pc['author'];
        $chapters = $pc['chapters'];
        $total = count($chapters);

        // 目次 body = work intro + an ordered, linked chapter list.
        $links = [];
        foreach ($chapters as $k => $ch) {
            $no = $k + 1;
            $chapterSlug = sprintf('%s-%d', $workSlug, $no);
            $title = $this->chapterTitle($workTitle, $ch['label'], $no);
            $links[] = sprintf('%d. [%s](/%s/%s)', $no, $title, $this->typeSlug, $chapterSlug);
        }
        $intro = $pc['intro'] !== '' ? $pc['intro'] . "\n\n" : '';
        $indexBody = trim($intro . "## 目次\n\n" . implode("\n", $links));

        $written = $this->ensureRecord(
            $typeId,
            $workSlug,
            ['title' => $workTitle, 'author' => $author, 'body' => $indexBody],
            [],
            $workTitle,
            sprintf('%s — 全%d章', $workTitle, $total),
        );

        foreach ($chapters as $k => $ch) {
            $no = $k + 1;
            $chapterSlug = sprintf('%s-%d', $workSlug, $no);
            $title = $this->chapterTitle($workTitle, $ch['label'], $no);
            $excerpt = mb_substr(str_replace("\n", ' ', $ch['body']), 0, 140, 'UTF-8');
            $written += $this->ensureRecord(
                $typeId,
                $chapterSlug,
                ['title' => $title, 'author' => $author, 'body' => $ch['body'], 'series' => $workSlug],
                ['chapter_no' => $no, 'chapter_total' => $total],
                $title,
                $excerpt,
            );
        }

        return $written;
    }

    /**
     * Create + publish one record, idempotently (skip if its slug already exists).
     *
     * @param array<string, string> $texts
     * @param array<string, int>    $ints
     * @return int 1 if written, 0 if skipped
     */
    private function ensureRecord(int $typeId, string $slug, array $texts, array $ints, string $metaTitle, string $metaDescription): int
    {
        if ($this->backend->findEntityIdBySlug($slug, $typeId) !== null) {
            return 0;
        }
        $entityId = $this->backend->createEntity($typeId, $slug);
        foreach ($texts as $key => $value) {
            $this->backend->setTextField($entityId, $key, $value);
        }
        foreach ($ints as $key => $value) {
            $this->backend->setIntField($entityId, $key, $value);
        }
        $this->backend->publish($entityId, $typeId, $slug, $metaTitle, $metaDescription);

        return 1;
    }

    /** Build a chapter record title, e.g. "坊っちゃん 第三章" (numeral) or "坊っちゃん 出発" (named). */
    private function chapterTitle(string $workTitle, string $label, int $no): string
    {
        if ($label === '') {
            return sprintf('%s 第%d章', $workTitle, $no);
        }
        if (AozoraParser::isNumeralLabel($label)) {
            return sprintf('%s 第%s章', $workTitle, $label);
        }

        return sprintf('%s %s', $workTitle, $label);
    }

    private static function clip(string $s): string
    {
        return mb_strlen($s, 'UTF-8') > 24 ? mb_substr($s, 0, 23, 'UTF-8') . '…' : $s;
    }
}

/* ============================== main ============================== */

// Allow this file to be required (e.g. by a test/validation harness) without
// running the importer: only execute the CLI flow when invoked directly.
if ((realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: '') !== __FILE__) {
    return;
}

$opts = getopt('', [
    'mode::', 'base::', 'email::', 'password::', 'org::', 'person::',
    'limit::', 'type-slug::', 'type-name::', 'body-type::', 'delay::', 'dry', 'chapters', 'work::',
]);

$mode = (string) ($opts['mode'] ?? 'http');
$person = (string) ($opts['person'] ?? '000148');
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 8;
$typeSlug = (string) ($opts['type-slug'] ?? 'work');
$typeName = (string) ($opts['type-name'] ?? '作品');
$bodyType = (string) ($opts['body-type'] ?? 'markdown');
$dry = array_key_exists('dry', $opts);
$chapters = array_key_exists('chapters', $opts);
$workFilter = isset($opts['work']) && (string) $opts['work'] !== ''
    ? array_values(array_filter(array_map('trim', explode(',', (string) $opts['work']))))
    : [];

try {
    if ($dry) {
        // No backend needed; fetch + parse only.
        $backend = new class () implements ImportBackend {
            public function ensureType(string $slug, string $name): int
            {
                return 0;
            }

            public function ensureFieldDef(int $typeId, string $fieldKey, string $dataType): void
            {
            }

            public function findEntityIdBySlug(string $slug, int $typeId): ?int
            {
                return null;
            }

            public function createEntity(int $typeId, string $slug): int
            {
                return 0;
            }

            public function setTextField(int $entityId, string $fieldKey, string $value): void
            {
            }

            public function setIntField(int $entityId, string $fieldKey, int $value): void
            {
            }

            public function ensurePermalinkPattern(int $typeId, string $name, string $slug, string $pattern): void
            {
            }

            public function publish(int $entityId, int $typeId, string $slug, string $metaTitle, string $metaDescription): void
            {
            }

            public function apiCallCount(): int
            {
                return 0;
            }
        };
    } elseif ($mode === 'direct') {
        $orgSlug = (string) ($opts['org'] ?? (getenv('ORG_SLUG') ?: 'aozora'));
        $backend = new DirectBackend($orgSlug);
        fprintf(STDERR, "Direct backend ready (org '%s').\n", $orgSlug);
    } else {
        $base = rtrim((string) ($opts['base'] ?? 'http://localhost:18082'), '/');
        $delay = isset($opts['delay']) ? (float) $opts['delay'] : 0.7;
        $email = (string) ($opts['email'] ?? (getenv('NENE_INSTALL_ADMIN_EMAIL') ?: 'admin@aozora.local'));
        $password = (string) ($opts['password'] ?? (getenv('NENE_INSTALL_ADMIN_PASSWORD') ?: 'soaktest1234'));
        $http = new HttpBackend($base, $delay);
        $who = $http->login($email, $password);
        fprintf(STDERR, "HTTP backend ready (%s, org_id=%s, delay=%.2fs).\n", $base, (string) ($who['org_id'] ?? '?'), $delay);
        $backend = $http;
    }

    (new Importer($backend, $person, $typeSlug, $typeName, $bodyType, $dry, $chapters, $workFilter))->run($limit);
} catch (Throwable $e) {
    fwrite(STDERR, 'IMPORT FAILED: ' . $e->getMessage() . "\n");
    exit(1);
}
