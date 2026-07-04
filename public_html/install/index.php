<?php

declare(strict_types=1);

/**
 * NeNe Records — 共有ホスティング Tier A インストーラ（#707）。
 *
 * Suite catalog 規約（installEntry: /install/index.php）に従う実ファイル。
 * .htaccess の front controller より先に Apache が直接配るため、アプリの
 * ルーティングには一切干渉しない。
 *
 * 配布 ZIP は vendor/ 同梱（invoice の Feature B のような pre-vendor 相は無い）
 * なので、vendor 不在＝ZIP が壊れているか展開漏れ。その場合だけ静的エラーを
 * 返し、以降は NENE2 の `Nene2\Install` toolkit（v1.6.0）をフルに配線する。
 *
 * CSRF は意図的に未装備（未設置状態でのみ動く一回きりのウィザードで、設置後は
 * ReInstallationGuard が全アクセスを遮断する — toolkit の設計と同じ判断）。
 *
 * スライス構成（事前契約 2026-07-04・関所承認済み）:
 *   S3-1: ガード＋要件チェック＋ステップ機械
 *   S3-2: DB 設定 → .env（契約全量）→ migrate → tenant（system_config＋env mirror）
 *          → 管理者（InstallApplication）→ marker → 完了
 */

use Nene2\Config\ConfigLoader;
use Nene2\Http\RequestScopedHolder;
use Nene2\Install\DatabaseSchemaApplier;
use Nene2\Install\DefaultInstallerMessages;
use Nene2\Install\EnvironmentWriter;
use Nene2\Install\Html;
use Nene2\Install\InstallerFlow;
use Nene2\Install\InstallerMessages;
use Nene2\Install\InstallerRenderer;
use Nene2\Install\InstallerStep;
use Nene2\Install\InstallerTemplate;
use Nene2\Install\ProvisioningProbe;
use Nene2\Install\ReInstallationGuard;
use Nene2\Install\RequirementCheck;
use Nene2\Install\ServerRequirementChecker;
use Nene2\Install\ServerRequirements;
use Nene2\Install\TenantConfigurationValidator;
use NeNeRecords\ApplicationServiceProvider;
use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Install\InstallApplication;
use NeNeRecords\Install\InstallConfig;
use NeNeRecords\Organization\CreateOrganizationUseCaseInterface;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\User\CreateUserUseCaseInterface;

define('ROOT', dirname(__DIR__, 2));
define('ENV_FILE', ROOT . '/.env');
define('INSTALLED_MARKER', ROOT . '/var/.installed');

// -------------------------------------------------------------------
// vendor ガード（toolkit 以前・静的）
// -------------------------------------------------------------------
if (!file_exists(ROOT . '/vendor/autoload.php')) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ja"><meta charset="utf-8"><title>NeNe Records インストール</title>'
        . '<body style="font-family:sans-serif;max-width:40rem;margin:4rem auto;padding:0 1rem">'
        . '<h1>展開が不完全です</h1>'
        . '<p>vendor/ ディレクトリが見つかりません。配布 ZIP をすべて展開（アップロード）してから、もう一度このページを開いてください。</p>'
        . '</body></html>';

    exit;
}

require ROOT . '/vendor/autoload.php';

// phinx.php の paths（database/migrations）と ConfigLoader は CLI（リポジトリ
// ルート実行）前提の相対参照を含む。Web リクエストの cwd は public_html/install/
// なので、ここで揃えないと phinx が migration を 0 本と認識して「成功」する。
chdir(ROOT);

// toolkit の checker は診断のみ（FS 非変更）なので、マーカー書込みが依存する
// var/ はここで明示的に補完する（S2 invoice と同じ判断）。
if (!is_dir(ROOT . '/var')) {
    @mkdir(ROOT . '/var', 0755, true);
}

// -------------------------------------------------------------------
// 再設置ガード — marker ＋ DB probe（防御の二層目）
// -------------------------------------------------------------------

/**
 * データベースに設置済みインスタンスがあるか。「admin が居る＝設置完了」を正とする
 * （migrate 済みでも admin 未作成ならウィザードは続行可能であるべき）。到達不能・
 * 未スキーマは「未設置」に倒す（probe 契約どおり throw しない）。
 *
 * ⚠️ .env 不在でも必ず probe する（#713）: Docker 本番は DB 接続情報をコンテナ
 * 環境変数で渡し .env を持たない。ConfigLoader は .env が無ければ実環境変数を
 * 読むので、そのまま接続を試みる。「.env 無し＝未設置」と早期 return すると
 * 設置済み本番でガードが素通しになる（実際に起きた）。
 */
function databaseProvisioned(): bool
{
    try {
        $database = (new ConfigLoader(ROOT))->load()->database;

        if ($database->usesUrl() && is_string($database->url)) {
            $parts = parse_url($database->url);

            if ($parts === false) {
                return false;
            }

            $host = (string) ($parts['host'] ?? 'localhost');
            $port = (int) ($parts['port'] ?? 3306);
            $name = ltrim((string) ($parts['path'] ?? ''), '/');
            $user = (string) ($parts['user'] ?? '');
            $password = (string) ($parts['pass'] ?? '');
        } else {
            $host = $database->host;
            $port = $database->port;
            $name = $database->name;
            $user = $database->user;
            $password = $database->password;
        }

        $count = connectDatabase($host, $port, $name, $user, $password)
            ->query('SELECT COUNT(*) FROM users')?->fetchColumn();

        return is_numeric($count) && (int) $count > 0;
    } catch (Throwable) {
        return false;
    }
}

function connectDatabase(string $host, int $port, string $name, string $user, string $password): PDO
{
    return new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name),
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
    );
}

$guard = new ReInstallationGuard(INSTALLED_MARKER, new class () implements ProvisioningProbe {
    public function isProvisioned(): bool
    {
        return databaseProvisioned();
    }
});

// -------------------------------------------------------------------
// サーバー要件（正: tools/hosting-precheck.php と同一リスト）
// -------------------------------------------------------------------

/** @return list<RequirementCheck> */
function requirementChecks(): array
{
    return (new ServerRequirementChecker())->check(new ServerRequirements(
        minPhpVersion: '8.4.1',
        requiredExtensions: ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'ctype', 'fileinfo', 'dom', 'libxml', 'gd'],
        writablePaths: [ROOT . '/var', ROOT],
        requiredFiles: [ROOT . '/phinx.php', ROOT . '/frontend/dist/index.html'],
    ));
}

/**
 * 推奨拡張（欠けても任意機能が無効になるだけ）。必須と同じ checker を素通しして
 * 非ブロッキング表示に使う。
 *
 * @return list<RequirementCheck>
 */
function recommendedChecks(): array
{
    return (new ServerRequirementChecker())->check(new ServerRequirements(
        minPhpVersion: '8.4.1',
        requiredExtensions: ['curl', 'simplexml', 'xmlreader', 'tokenizer', 'filter'],
    ));
}

// -------------------------------------------------------------------
// 設置コンテキストの導出
// -------------------------------------------------------------------

/**
 * サブディレクトリ設置の APP_BASE_PATH を install URL から自動導出する
 * （/blog/install/… → '/blog'、ルート設置 → ''）。
 */
function derivedBasePath(): string
{
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $base = (string) preg_replace('#/install(?:/.*)?$#', '', $path);

    return $base === '' || $base === '/' ? '' : rtrim($base, '/');
}

/** 現在のホスト名（ポート除去）。BASE_DOMAIN 既定値と MAIL_FROM に使う。 */
function currentHost(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    if (str_contains($host, ':')) {
        $host = explode(':', $host)[0];
    }

    return $host !== '' ? $host : 'localhost';
}

// -------------------------------------------------------------------
// 提示契約（flow / messages / template）
// -------------------------------------------------------------------

$flow = new InstallerFlow([
    new InstallerStep('requirements'),
    new InstallerStep('database', ['db_host', 'db_port', 'db_name', 'db_user', 'db_pass', 'tenant_mode', 'base_domain', 'org_slug']),
    new InstallerStep('administrator', ['org_name', 'admin_email', 'admin_password']),
    new InstallerStep('complete'),
]);

$messages = new class () implements InstallerMessages {
    private const JA = [
        'marker_present' => 'このサイトは設置済みです（var/.installed を検出）。再設置が必要な場合はサーバー上の var/.installed を削除してください。',
        'database_provisioned' => 'データベースに設置済みの NeNe Records を検出したため、インストーラは実行できません。',
        'php_too_old' => 'PHP のバージョンが要件（8.4.1 以上）を満たしていません。',
        'extension_missing' => '必要な PHP 拡張がロードされていません。',
        'not_writable' => '書き込みできないディレクトリがあります。',
        'missing' => '必要なファイルが見つかりません。',
        'base_domain_required' => 'サブドメイン型では基準ドメイン（BASE_DOMAIN）が必須です。',
        'base_domain_invalid' => '基準ドメインの形式が正しくありません（英数字・ドット・ハイフンのみ）。',
    ];

    public function forReasonCode(string $reasonCode): string
    {
        return self::JA[$reasonCode] ?? (new DefaultInstallerMessages())->forReasonCode($reasonCode);
    }
};

$template = new class ($flow, $messages, $guard) implements InstallerTemplate {
    public function __construct(
        private readonly InstallerFlow $installerFlow,
        private readonly InstallerMessages $installerMessages,
        private readonly ReInstallationGuard $reInstallationGuard,
    ) {
    }

    public function flow(): InstallerFlow
    {
        return $this->installerFlow;
    }

    public function messages(): InstallerMessages
    {
        return $this->installerMessages;
    }

    public function guard(): ReInstallationGuard
    {
        return $this->reInstallationGuard;
    }
};

// -------------------------------------------------------------------
// 表示ヘルパー
// -------------------------------------------------------------------

function renderPage(string $body): never
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ja"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex">'
        . '<title>NeNe Records インストール</title>'
        . '<style>'
        . 'body{font-family:system-ui,sans-serif;background:#f4f5f7;color:#1f2328;margin:0}'
        . '.wrap{max-width:44rem;margin:3rem auto;padding:0 1rem}'
        . '.card{background:#fff;border:1px solid #d7dbe0;border-radius:8px;padding:1.5rem 2rem;margin-bottom:1rem}'
        . 'h1{font-size:1.25rem}h2{font-size:1rem;margin-top:0}'
        . 'table{border-collapse:collapse;width:100%}td,th{padding:.4rem .5rem;border-bottom:1px solid #eceff2;text-align:left;font-size:.9rem}'
        . '.ok{color:#116329}.ng{color:#a40e26;font-weight:600}.warn{color:#7d4e00}'
        . '.installer-progress{color:#57606a;font-size:.85rem}'
        . '.installer-errors{color:#a40e26;background:#fff8f8;border:1px solid #e0b4b4;border-radius:6px;padding:.75rem 1rem .75rem 2rem}'
        . '.installer-blocked{background:#fff8f8;border:1px solid #e0b4b4;border-radius:8px;padding:1rem 1.5rem}'
        . '.installer-field{display:block;margin:.75rem 0;font-size:.9rem}'
        . '.installer-field input,.installer-field select{display:block;width:100%;box-sizing:border-box;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #d7dbe0;border-radius:6px;font-size:1rem}'
        . '.btn{display:inline-block;background:#1f6feb;color:#fff;border:none;border-radius:6px;padding:.5rem 1.25rem;text-decoration:none;font-size:1rem;cursor:pointer}'
        . '.muted{color:#57606a;font-size:.85rem}'
        . 'details{margin:.75rem 0}summary{cursor:pointer;color:#57606a}'
        . '</style></head><body><div class="wrap">'
        . '<h1>NeNe Records インストール</h1>'
        . $body
        . '</div></body></html>';

    exit;
}

/** 要件行の日本語ラベル（target → 説明） */
function requirementLabel(RequirementCheck $check): string
{
    $extensions = [
        'pdo' => 'データベース接続（PDO）',
        'pdo_mysql' => 'MySQL ドライバ',
        'mbstring' => 'マルチバイト文字列処理',
        'json' => 'JSON',
        'openssl' => '署名・暗号（JWT / TLS）',
        'ctype' => '入力検証',
        'fileinfo' => 'メディアの MIME 判定',
        'dom' => 'SVG サニタイズ・WordPress 取込',
        'libxml' => 'XML パーサ',
        'gd' => '画像処理（派生画像）',
        'curl' => 'Webhook・通知の送信',
        'simplexml' => 'WordPress（WXR）取込',
        'xmlreader' => '大型 WXR のストリーミング取込',
        'tokenizer' => '開発ツール系',
        'filter' => '入力フィルタ',
    ];

    return match ($check->requirement) {
        ServerRequirementChecker::REQUIREMENT_PHP => 'PHP ' . $check->target . ' 以上（現在 ' . PHP_VERSION . '）',
        ServerRequirementChecker::REQUIREMENT_EXTENSION => '拡張 ' . $check->target . ' — ' . ($extensions[$check->target] ?? ''),
        ServerRequirementChecker::REQUIREMENT_WRITABLE => '書き込み: ' . ($check->target === ROOT ? 'アプリルート（.env の書き込み先）' : str_replace(ROOT . '/', '', $check->target) . '/'),
        ServerRequirementChecker::REQUIREMENT_FILE => 'ファイル: ' . str_replace(ROOT . '/', '', $check->target),
        default => $check->target,
    };
}

/** @param list<RequirementCheck> $checks */
function requirementTable(array $checks, bool $blocking): string
{
    $rows = '';

    foreach ($checks as $check) {
        if ($check->satisfied) {
            $status = '<span class="ok">OK</span>';
        } else {
            $reason = $check->reasonCodes[0] ?? '';
            $label = match ($reason) {
                'php_too_old' => 'バージョン不足',
                'extension_missing' => '未ロード',
                'not_writable' => '書き込み不可',
                'missing' => '見つかりません',
                default => 'NG',
            };
            $status = '<span class="' . ($blocking ? 'ng' : 'warn') . '">' . Html::escape($label) . '</span>';
        }

        $rows .= '<tr><td>' . Html::escape(requirementLabel($check)) . '</td><td>' . $status . '</td></tr>';
    }

    return '<table>' . $rows . '</table>';
}

/**
 * 入力フィールド 1 行。参照レンダラ（InstallerRenderer）の class 語彙に合わせるが、
 * 参照レンダラは全 input が type="text"（パスワード平文表示）のため、入力型が要る
 * ステップは製品側で描画する（NENE2 #1482 が入り次第置換できる構造）。
 */
function fieldRow(string $label, string $name, string $value, string $type = 'text', string $hint = ''): string
{
    return '<label class="installer-field">' . Html::escape($label)
        . '<input type="' . Html::escape($type) . '" name="' . Html::escape($name) . '" value="' . Html::escape($value) . '">'
        . ($hint !== '' ? '<span class="muted">' . Html::escape($hint) . '</span>' : '')
        . '</label>';
}

/** @param list<string> $errors */
function errorList(array $errors): string
{
    if ($errors === []) {
        return '';
    }

    $items = '';

    foreach ($errors as $error) {
        $items .= '<li>' . Html::escape($error) . '</li>';
    }

    return '<ul class="installer-errors">' . $items . '</ul>';
}

function progressLine(InstallerFlow $flow, string $stepId): string
{
    return '<p class="installer-progress">ステップ ' . $flow->position($stepId) . ' / ' . $flow->count() . '</p>';
}

// -------------------------------------------------------------------
// ルーティング
// -------------------------------------------------------------------

$renderer = new InstallerRenderer();

// ガード（毎リクエスト・全ステップ共通）。blocked の描画は参照レンダラに委譲し、
// ステータスは invoice と同じ再訪 403 で返す（成功画面と機械的に区別できるように）。
if ($guard->isBlocked()) {
    http_response_code(403);
    renderPage($renderer->render($template, 'requirements'));
}

$stepParam = $_GET['step'] ?? 'requirements';
$stepId = is_string($stepParam) && $flow->has($stepParam) ? $stepParam : 'requirements';
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

// ================= ステップ 1: サーバー要件 =================
if ($stepId === 'requirements') {
    $checks = requirementChecks();
    $recommended = recommendedChecks();
    $allOk = ServerRequirementChecker::allSatisfied($checks);

    // 推奨リストの先頭は PHP バージョン行（必須側と重複）なので表示から除く。
    $recommendedRows = array_values(array_filter(
        $recommended,
        static fn (RequirementCheck $c): bool => $c->requirement === ServerRequirementChecker::REQUIREMENT_EXTENSION,
    ));

    renderPage(
        '<div class="card">'
        . progressLine($flow, 'requirements')
        . '<h2>サーバー要件の確認</h2>'
        . requirementTable($checks, true)
        . '</div>'
        . '<div class="card"><h2>推奨（無くても設置できます）</h2>'
        . requirementTable($recommendedRows, false)
        . '</div>'
        . ($allOk
            ? '<a class="btn" href="?step=database">続行 →</a>'
            : '<a class="btn" href="?step=requirements">再チェック</a> <p class="muted">未達の項目を解消してから再チェックしてください。</p>'),
    );
}

// ================= ステップ 2: データベースとサイト設定 =================
if ($stepId === 'database') {
    $errors = [];
    $values = [
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_name' => '',
        'db_user' => '',
        'tenant_mode' => 'single',
        'base_domain' => '',
        'org_slug' => 'default',
    ];

    if ($isPost) {
        foreach (array_keys($values) as $key) {
            $posted = $_POST[$key] ?? null;

            if (is_string($posted)) {
                $values[$key] = trim($posted);
            }
        }

        $dbPass = is_string($_POST['db_pass'] ?? null) ? (string) $_POST['db_pass'] : '';
        $tenantMode = in_array($values['tenant_mode'], ['single', 'subdomain', 'path'], true)
            ? $values['tenant_mode']
            : 'single';
        $orgSlug = $values['org_slug'] !== '' ? $values['org_slug'] : 'default';

        if ($values['db_name'] === '' || $values['db_user'] === '') {
            $errors[] = 'データベース名とユーザー名は必須です。';
        }

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $orgSlug) !== 1) {
            $errors[] = '組織スラッグは英小文字・数字・ハイフン（先頭末尾は英数字）で入力してください。';
        }

        // tenant 検証は toolkit へ委譲（records の 3 語彙・subdomain のみ基準ドメイン必須）。
        $tenantResult = (new TenantConfigurationValidator(['single', 'subdomain', 'path'], ['subdomain']))
            ->validate($tenantMode, $values['base_domain']);

        if (!$tenantResult->valid) {
            foreach ($tenantResult->errors as $code) {
                $errors[] = $messages->forReasonCode($code);
            }
        }

        $pdo = null;

        if ($errors === []) {
            try {
                $pdo = connectDatabase($values['db_host'], (int) $values['db_port'], $values['db_name'], $values['db_user'], $dbPass);
                $pdo->query('SELECT 1');
            } catch (Throwable $e) {
                $code = $e instanceof PDOException ? (string) $e->getCode() : '';
                $errors[] = 'データベースに接続できません。接続情報をご確認ください。'
                    . ($code !== '' ? '（エラーコード: ' . $code . '）' : '');
            }
        }

        if ($errors === [] && $pdo instanceof PDO) {
            $baseDomain = $tenantResult->configuration?->baseDomain ?? '';
            $effectiveDomain = $baseDomain !== '' ? $baseDomain : currentHost();

            $env = [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_NAME' => 'NeNe Records',
                'NENE2_MACHINE_API_KEY' => '',
                'NENE2_LOCAL_JWT_SECRET' => EnvironmentWriter::generateSecret(),
                'PROBLEM_DETAILS_BASE_URL' => 'https://nene-records.dev/problems/',
                'DATABASE_URL' => '',
                'DB_ENV' => 'production',
                'DB_ADAPTER' => 'mysql',
                'DB_HOST' => $values['db_host'],
                'DB_PORT' => $values['db_port'],
                'DB_NAME' => $values['db_name'],
                'DB_USER' => $values['db_user'],
                'DB_PASSWORD' => $dbPass,
                'DB_CHARSET' => 'utf8mb4',
                'MAIL_DSN' => 'null://null',
                'MAIL_FROM_ADDRESS' => 'noreply@' . currentHost(),
                'MAIL_FROM_NAME' => 'NeNe Records',
                'TENANT_RESOLUTION' => $tenantMode,
                'ORG_SLUG' => $orgSlug,
                'BASE_DOMAIN' => $effectiveDomain,
                'APP_BASE_PATH' => derivedBasePath(),
                'MEDIA_STORAGE_DRIVER' => 'local',
            ];

            try {
                (new EnvironmentWriter())->write(ENV_FILE, $env);

                // 再提出時の取り違え防止: 冒頭の probe が旧 .env を $_ENV へ読み込んで
                // いる可能性があり、phpdotenv（immutable）は既存キーを上書きしないため、
                // 今書いた値でプロセス内の環境を明示的に上書きしてから migrate する。
                foreach ($env as $key => $value) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv($key . '=' . $value);
                }

                // スキーマの正は migration（手書き dump は作らない・S2 決定A の踏襲）。
                (new DatabaseSchemaApplier())->applyFromPhpConfig(ROOT . '/phinx.php');

                // tenant 設定は system_config が正・env は fallback 層（#582）。共有
                // ホスティングでは getenv 系 fallback が効かない場合があるため、
                // system_config を必ず実値で埋める。
                $statement = $pdo->prepare(
                    'INSERT INTO system_config (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
                );
                $statement->execute(['tenant_resolution_mode', $tenantMode]);
                $statement->execute(['tenant_org_slug', $orgSlug]);
                $statement->execute(['tenant_base_domain', $effectiveDomain]);

                header('Location: ?step=administrator');

                exit;
            } catch (Throwable $e) {
                // toolkit の例外 message は秘匿情報を含まない設計（EnvironmentWriter /
                // DatabaseSchemaApplier）だが、表示は要約に留める。
                $errors[] = '設置処理に失敗しました: ' . $e->getMessage();
            }
        }
    }

    renderPage(
        '<div class="card">'
        . progressLine($flow, 'database')
        . '<h2>データベースと利用形態</h2>'
        . errorList($errors)
        . '<form method="post">'
        . fieldRow('データベースホスト', 'db_host', $values['db_host'])
        . fieldRow('ポート', 'db_port', $values['db_port'])
        . fieldRow('データベース名', 'db_name', $values['db_name'], 'text', 'ホスティングの管理画面で作成した MySQL データベース名')
        . fieldRow('ユーザー名', 'db_user', $values['db_user'])
        . fieldRow('パスワード', 'db_pass', '', 'password')
        . '<details><summary>詳細設定（マルチテナント・組織スラッグ）</summary>'
        . '<label class="installer-field">利用形態'
        . '<select name="tenant_mode">'
        . '<option value="single"' . ($values['tenant_mode'] === 'single' ? ' selected' : '') . '>単一サイト（標準）</option>'
        . '<option value="subdomain"' . ($values['tenant_mode'] === 'subdomain' ? ' selected' : '') . '>マルチテナント: サブドメイン型</option>'
        . '<option value="path"' . ($values['tenant_mode'] === 'path' ? ' selected' : '') . '>マルチテナント: パス型</option>'
        . '</select></label>'
        . fieldRow('基準ドメイン', 'base_domain', $values['base_domain'], 'text', 'サブドメイン型のときのみ必須（例: example.com）')
        . fieldRow('組織スラッグ', 'org_slug', $values['org_slug'], 'text', '通常は default のままで構いません')
        . '</details>'
        . '<button class="btn" type="submit">設定を保存して設置する</button>'
        . '<p class="muted">保存すると .env の書き込みとデータベースの初期化（migration）を実行します。</p>'
        . '</form></div>',
    );
}

// ================= ステップ 3: 管理者アカウント =================
if ($stepId === 'administrator') {
    if (!is_file(ENV_FILE)) {
        renderPage(
            '<div class="card">'
            . progressLine($flow, 'administrator')
            . '<p>先にデータベース設定を完了してください。</p>'
            . '<p><a href="?step=database">← データベース設定へ</a></p></div>',
        );
    }

    $errors = [];
    $orgName = 'NeNe Records';
    $adminEmail = '';

    if ($isPost) {
        $orgName = is_string($_POST['org_name'] ?? null) && trim((string) $_POST['org_name']) !== ''
            ? trim((string) $_POST['org_name'])
            : 'NeNe Records';
        $adminEmail = is_string($_POST['admin_email'] ?? null) ? trim((string) $_POST['admin_email']) : '';
        $adminPassword = is_string($_POST['admin_password'] ?? null) ? (string) $_POST['admin_password'] : '';

        if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'メールアドレスの形式が正しくありません。';
        }

        if (strlen($adminPassword) < 8) {
            $errors[] = 'パスワードは 8 文字以上で入力してください。';
        }

        if ($errors === []) {
            try {
                $container = (new RuntimeContainerFactory(ROOT))->create();

                $createOrganization = $container->get(CreateOrganizationUseCaseInterface::class);
                $organizations = $container->get(OrganizationRepositoryInterface::class);
                $createUser = $container->get(CreateUserUseCaseInterface::class);
                $orgHolder = $container->get(ApplicationServiceProvider::ORG_ID_HOLDER);

                if (
                    !$createOrganization instanceof CreateOrganizationUseCaseInterface
                    || !$organizations instanceof OrganizationRepositoryInterface
                    || !$createUser instanceof CreateUserUseCaseInterface
                    || !$orgHolder instanceof RequestScopedHolder
                ) {
                    throw new RuntimeException('アプリケーションコンテナの構成が不正です。');
                }

                // org slug は database ステップで .env に書いた値（ConfigLoader が
                // $_ENV へ読み込む）。冪等な InstallApplication（#573）に委譲するので、
                // 並行タブの二重送信でも二重作成にはならない。
                $orgSlug = is_string($_ENV['ORG_SLUG'] ?? null) && $_ENV['ORG_SLUG'] !== ''
                    ? (string) $_ENV['ORG_SLUG']
                    : 'default';

                /** @var RequestScopedHolder<int> $orgHolder */
                $result = (new InstallApplication($createOrganization, $organizations, $createUser, $orgHolder))
                    ->install(new InstallConfig($orgName, $orgSlug, $adminEmail, $adminPassword));

                $guard->markInstalled(date('c'));

                $loginUrl = derivedBasePath() . '/login';

                // marker 書込済みのためリダイレクトすると 403 ガードに当たる —
                // 完了画面は同一レスポンスで返す（リロード時に 403 なのは正しい挙動）。
                renderPage(
                    '<div class="card">'
                    . progressLine($flow, 'complete')
                    . '<h2>設置が完了しました 🎉</h2>'
                    . '<table>'
                    . '<tr><td>組織</td><td>' . Html::escape($result->organizationSlug) . '（' . ($result->organizationCreated ? '新規作成' : '既存') . '）</td></tr>'
                    . '<tr><td>管理者</td><td>' . Html::escape($result->adminEmail) . '（' . ($result->adminCreated ? '新規作成' : '既存') . '）</td></tr>'
                    . '</table>'
                    . '<p><a class="btn" href="' . Html::escape($loginUrl) . '">管理画面にログイン →</a></p>'
                    . '<p class="muted">セキュリティのため、サーバー上の install/ ディレクトリは削除することをおすすめします（削除しなくても再実行は遮断されます）。</p>'
                    . '</div>',
                );
            } catch (Throwable $e) {
                $errors[] = '管理者の作成に失敗しました: ' . $e->getMessage();
            }
        }
    }

    renderPage(
        '<div class="card">'
        . progressLine($flow, 'administrator')
        . '<h2>管理者アカウント</h2>'
        . errorList($errors)
        . '<form method="post">'
        . fieldRow('サイト名（組織名）', 'org_name', $orgName)
        . fieldRow('管理者メールアドレス', 'admin_email', $adminEmail)
        . fieldRow('管理者パスワード', 'admin_password', '', 'password', '8 文字以上')
        . '<button class="btn" type="submit">管理者を作成して完了</button>'
        . '</form></div>',
    );
}

// ================= ステップ 4: 完了（直接アクセス時のみ） =================
// 正常フローの完了画面は administrator ステップの同一レスポンスで返す（marker
// 書込後はガードが 403 を返すため）。ここに GET で来た＝未設置なので誘導する。
renderPage(
    '<div class="card">'
    . progressLine($flow, 'complete')
    . '<p>設置はまだ完了していません。</p>'
    . '<p><a href="?step=requirements">← 要件チェックへ</a></p></div>',
);
