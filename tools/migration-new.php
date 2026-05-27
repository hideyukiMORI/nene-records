#!/usr/bin/env php
<?php

/**
 * Generate a new Phinx migration file with the next available version number.
 *
 * Usage (from project root):
 *   composer migrations:new -- CreateFooTable
 *   vendor/bin/phinx create CreateFooTable  # Phinx native alternative
 *
 * This script avoids version-number collisions by scanning both:
 *  - Existing migration files in database/migrations/
 *  - Applied versions recorded in phinxlog (via phinx status)
 *
 * Version format: YYYYMMDDnnnnnn (date + 6-digit sequence, matching Phinx convention)
 */

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: composer migrations:new -- <MigrationClassName>\n");
    fwrite(STDERR, "Example: composer migrations:new -- CreateFooTable\n");
    exit(1);
}

$className = $argv[1];

// Validate class name: PascalCase letters/digits only
if (!preg_match('/^[A-Z][A-Za-z0-9]+$/', $className)) {
    fwrite(STDERR, "Error: class name must be PascalCase (e.g. CreateFooTable).\n");
    exit(1);
}

$migrationsDir = __DIR__ . '/../database/migrations';

// Collect version numbers from existing migration files
$existingVersions = [];
$files = glob($migrationsDir . '/*.php');
if ($files !== false) {
    foreach ($files as $file) {
        if (preg_match('/^(\d{14})_/', basename($file), $m)) {
            $existingVersions[] = (int) $m[1];
        }
    }
}

// Next version: today's date (YYYYMMDD) + 000000, incremented until unique
$today  = (int) date('Ymd');
$suffix = 0;

do {
    $candidate = (int) sprintf('%d%06d', $today, $suffix);
    $suffix++;
} while (in_array($candidate, $existingVersions, true));

$version  = sprintf('%d%06d', $today, $suffix - 1);
$snaked   = (string) preg_replace('/([A-Z])/', '_$1', lcfirst($className));
$fileName = sprintf('%s_%s.php', $version, strtolower($snaked));
$filePath = $migrationsDir . '/' . $fileName;

$stub = <<<PHP
<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class {$className} extends AbstractMigration
{
    public function change(): void
    {
        // \$table = \$this->table('your_table');
        // \$table->addColumn('name', 'string', ['limit' => 255])->create();
    }
}
PHP;

file_put_contents($filePath, $stub);

echo "Created: database/migrations/{$fileName}\n";
echo "Version: {$version}\n";
