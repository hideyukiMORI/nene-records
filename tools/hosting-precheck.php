<?php

/**
 * NeNe Records — shared-hosting precheck (zip-install gate / EPIC: deploy mode 2).
 *
 * Standalone, dependency-free. Upload this single file to a shared host (HETEML,
 * さくら, ロリポップ, エックスサーバー…) by FTP and open it in a browser — or run
 * `php hosting-precheck.php` over SSH — to see whether the host can run a
 * zip-installed NeNe Records (PHP 8.4 + the runtime extensions the app uses).
 *
 * It is READ-ONLY: it inspects PHP only, writes nothing, and (unless you pass
 * DB credentials) connects to nothing. Delete it after checking.
 *
 * Optional MySQL probe (only if you want it):
 *   web: hosting-precheck.php?db_host=localhost&db_name=...&db_user=...&db_pass=...
 *   cli: php hosting-precheck.php --db-host=localhost --db-name=... --db-user=... --db-pass=...
 */

declare(strict_types=1);

const REQUIRED_PHP = '8.4.1';

/** Extensions the core app genuinely needs (a missing one breaks core features). */
const REQUIRED_EXT = [
    'pdo' => 'Database access (PDO).',
    'pdo_mysql' => 'MySQL driver — the app stores everything in MySQL.',
    'mbstring' => 'Multibyte/UTF-8 string handling (i18n, validation).',
    'json' => 'JSON encode/decode (API + config).',
    'openssl' => 'JWT signing, password hashing, TLS to MySQL/SMTP.',
    'ctype' => 'Input validation primitives.',
    'fileinfo' => 'MIME detection for media uploads.',
    'dom' => 'XML DOM — SVG upload sanitisation and WXR import.',
    'libxml' => 'Underlying XML parser for dom/simplexml.',
    'gd' => 'Image processing / on-demand media derivatives.',
];

/** Nice-to-have: a missing one only disables an optional feature. */
const RECOMMENDED_EXT = [
    'curl' => 'Outbound webhooks and Slack/Discord notifications.',
    'simplexml' => 'WordPress (WXR) content import.',
    'xmlreader' => 'Streaming XML for large WXR imports.',
    'tokenizer' => 'Used by some tooling/templating.',
    'filter' => 'Input filtering helpers.',
];

/**
 * @return array{0: string, 1: string, 2: string} status row: [status, label, detail]
 *   status ∈ {ok, warn, fail}
 */
function row(string $status, string $label, string $detail): array
{
    return [$status, $label, $detail];
}

function parseBytes(string $value): int
{
    $value = trim($value);
    if ($value === '' || $value === '-1') {
        return -1; // unlimited / unset
    }
    $unit = strtolower($value[strlen($value) - 1]);
    $number = (int) $value;

    return match ($unit) {
        'g' => $number * 1024 * 1024 * 1024,
        'm' => $number * 1024 * 1024,
        'k' => $number * 1024,
        default => (int) $value,
    };
}

$rows = [];
$hardFail = false;

// ── PHP version ─────────────────────────────────────────────────────────────
$versionOk = version_compare(PHP_VERSION, REQUIRED_PHP, '>=') && version_compare(PHP_VERSION, '9.0.0', '<');
$rows[] = row(
    $versionOk ? 'ok' : 'fail',
    'PHP version',
    PHP_VERSION . ' (need >= ' . REQUIRED_PHP . ', < 9.0)',
);
$hardFail = $hardFail || !$versionOk;

// ── Required extensions ─────────────────────────────────────────────────────
foreach (REQUIRED_EXT as $ext => $why) {
    $loaded = extension_loaded($ext);
    $rows[] = row($loaded ? 'ok' : 'fail', "ext: {$ext}", $loaded ? $why : "MISSING — {$why}");
    $hardFail = $hardFail || !$loaded;
}

// ── Recommended extensions ──────────────────────────────────────────────────
foreach (RECOMMENDED_EXT as $ext => $why) {
    $loaded = extension_loaded($ext);
    $rows[] = row($loaded ? 'ok' : 'warn', "ext: {$ext}", $loaded ? $why : "missing — {$why}");
}

// ── INI settings ────────────────────────────────────────────────────────────
$memory = parseBytes((string) ini_get('memory_limit'));
$memoryOk = $memory === -1 || $memory >= 128 * 1024 * 1024;
$rows[] = row($memoryOk ? 'ok' : 'warn', 'memory_limit', (string) ini_get('memory_limit') . ' (recommend >= 128M)');

$fileUploads = (bool) ini_get('file_uploads');
$rows[] = row($fileUploads ? 'ok' : 'fail', 'file_uploads', $fileUploads ? 'On' : 'Off — media uploads need this');
$hardFail = $hardFail || !$fileUploads;

$upload = parseBytes((string) ini_get('upload_max_filesize'));
$uploadOk = $upload === -1 || $upload >= 8 * 1024 * 1024;
$rows[] = row($uploadOk ? 'ok' : 'warn', 'upload_max_filesize', (string) ini_get('upload_max_filesize') . ' (recommend >= 8M)');

$maxExec = (int) ini_get('max_execution_time');
$execOk = $maxExec === 0 || $maxExec >= 30;
$rows[] = row($execOk ? 'ok' : 'warn', 'max_execution_time', (string) $maxExec . 's (recommend 0 or >= 30 for migrations/import)');

// ── Writability (proxy for install dir) ─────────────────────────────────────
$dir = __DIR__;
$writable = is_writable($dir);
$rows[] = row($writable ? 'ok' : 'warn', 'writable dir', $dir . ($writable ? '' : ' — installer needs a writable path'));

// ── PDO MySQL drivers present ───────────────────────────────────────────────
if (extension_loaded('pdo')) {
    $drivers = \PDO::getAvailableDrivers();
    $hasMysql = in_array('mysql', $drivers, true);
    $rows[] = row($hasMysql ? 'ok' : 'fail', 'PDO drivers', implode(', ', $drivers) ?: '(none)');
    $hardFail = $hardFail || !$hasMysql;
}

// ── Optional MySQL connection probe ─────────────────────────────────────────
$cliArgs = is_array($_SERVER['argv'] ?? null) ? $_SERVER['argv'] : [];
$opt = static function (string $key) use ($cliArgs): ?string {
    $q = $_GET[$key] ?? null;
    if (is_string($q) && $q !== '') {
        return $q;
    }
    $flag = '--' . str_replace('_', '-', $key) . '=';
    foreach ($cliArgs as $arg) {
        if (str_starts_with((string) $arg, $flag)) {
            return substr((string) $arg, strlen($flag));
        }
    }

    return null;
};
$dbHost = $opt('db_host');
$dbName = $opt('db_name');
$dbUser = $opt('db_user');
$dbPass = $opt('db_pass');
if ($dbHost !== null && $dbName !== null && $dbUser !== null) {
    try {
        $pdo = new \PDO(
            "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
            $dbUser,
            $dbPass ?? '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5],
        );
        $serverVersion = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $rows[] = row('ok', 'MySQL connect', "OK — server {$serverVersion}");
    } catch (\Throwable $e) {
        $rows[] = row('fail', 'MySQL connect', 'FAILED — ' . $e->getMessage());
        $hardFail = true;
    }
}

// ── Render ──────────────────────────────────────────────────────────────────
$verdict = $hardFail ? 'NOT READY' : 'READY';
$isCli = PHP_SAPI === 'cli';

if ($isCli) {
    $mark = ['ok' => '[ OK ]', 'warn' => '[WARN]', 'fail' => '[FAIL]'];
    echo "NeNe Records — hosting precheck\n";
    echo str_repeat('=', 60) . "\n";
    foreach ($rows as [$status, $label, $detail]) {
        echo sprintf("%s  %-22s %s\n", $mark[$status], $label, $detail);
    }
    echo str_repeat('=', 60) . "\n";
    echo "VERDICT: {$verdict}" . ($hardFail ? " — fix [FAIL] rows above.\n" : " — this host can run NeNe Records.\n");
    exit($hardFail ? 1 : 0);
}

$color = ['ok' => '#16794a', 'warn' => '#9a6700', 'fail' => '#b42318'];
$badge = ['ok' => '✓', 'warn' => '!', 'fail' => '✗'];
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>NeNe Records — hosting precheck</title>';
echo '<style>body{font:15px/1.6 system-ui,sans-serif;max-width:780px;margin:2rem auto;padding:0 1rem;color:#1a1a1a}'
    . 'h1{font-size:1.3rem}table{border-collapse:collapse;width:100%}td{padding:.45rem .6rem;border-bottom:1px solid #eee;vertical-align:top}'
    . '.b{font-weight:700;width:1.4rem;text-align:center}.l{white-space:nowrap;font-family:ui-monospace,monospace}.d{color:#555}'
    . '.verdict{font-size:1.15rem;font-weight:700;padding:.8rem 1rem;border-radius:.5rem;margin:1rem 0}'
    . '.pass{background:#e6f4ea;color:#16794a}.no{background:#fde8e6;color:#b42318}</style>';
echo '<h1>NeNe Records — hosting precheck</h1>';
echo '<div class="verdict ' . ($hardFail ? 'no' : 'pass') . '">' . htmlspecialchars($verdict)
    . ($hardFail ? ' — fix the ✗ rows below.' : ' — this host can run NeNe Records.') . '</div>';
echo '<table>';
foreach ($rows as [$status, $label, $detail]) {
    echo '<tr><td class="b" style="color:' . $color[$status] . '">' . $badge[$status] . '</td>'
        . '<td class="l">' . htmlspecialchars($label) . '</td>'
        . '<td class="d">' . htmlspecialchars($detail) . '</td></tr>';
}
echo '</table>';
echo '<p class="d">Read-only. Delete this file after checking.</p>';
