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
 *   S3-1（本コミット）: ガード＋要件チェック＋ステップ機械（database 以降は placeholder）
 *   S3-2: DB/site 設定 → .env → migrate → tenant → 管理者 → 完了
 */

use Nene2\Config\ConfigLoader;
use Nene2\Install\DefaultInstallerMessages;
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
 * 未スキーマ・.env 不在はすべて「未設置」に倒す（probe 契約どおり throw しない）。
 */
function databaseProvisioned(): bool
{
    if (!is_file(ENV_FILE)) {
        return false;
    }

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

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name),
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
        );

        $count = $pdo->query('SELECT COUNT(*) FROM users')?->fetchColumn();

        return is_numeric($count) && (int) $count > 0;
    } catch (Throwable) {
        return false;
    }
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
// 提示契約（flow / messages / template）
// -------------------------------------------------------------------

$flow = new InstallerFlow([
    new InstallerStep('requirements'),
    new InstallerStep('database', ['db_host', 'db_port', 'db_name', 'db_user', 'db_pass', 'site_name', 'tenant_mode', 'base_domain']),
    new InstallerStep('administrator', ['admin_email', 'admin_password', 'org_name']),
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
        . '.installer-errors{color:#a40e26}'
        . '.installer-blocked{background:#fff8f8;border:1px solid #e0b4b4;border-radius:8px;padding:1rem 1.5rem}'
        . '.btn{display:inline-block;background:#1f6feb;color:#fff;border-radius:6px;padding:.5rem 1.25rem;text-decoration:none}'
        . '.btn.disabled{background:#8c959f;pointer-events:none}'
        . '.muted{color:#57606a;font-size:.85rem}'
        . '</style></head><body><div class="wrap">'
        . '<h1>NeNe Records インストール</h1>'
        . $body
        . '</div></body></html>';

    exit;
}

/** @var array<string, string> 要件行の日本語ラベル（target → 説明） */
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

if ($stepId === 'requirements') {
    $checks = requirementChecks();
    $recommended = recommendedChecks();
    $allOk = ServerRequirementChecker::allSatisfied($checks);

    // 推奨リストの先頭は PHP バージョン行（必須側と重複）なので表示から除く。
    $recommendedRows = array_values(array_filter(
        $recommended,
        static fn (RequirementCheck $c): bool => $c->requirement === ServerRequirementChecker::REQUIREMENT_EXTENSION,
    ));

    $body = '<div class="card">'
        . '<p class="installer-progress">ステップ ' . $flow->position('requirements') . ' / ' . $flow->count() . '</p>'
        . '<h2>サーバー要件の確認</h2>'
        . requirementTable($checks, true)
        . '</div>'
        . '<div class="card"><h2>推奨（無くても設置できます）</h2>'
        . requirementTable($recommendedRows, false)
        . '</div>'
        . ($allOk
            ? '<a class="btn" href="?step=database">続行 →</a>'
            : '<a class="btn" href="?step=requirements">再チェック</a> <p class="muted">未達の項目を解消してから再チェックしてください。</p>');

    renderPage($body);
}

// database / administrator / complete は S3-2 で配線する（骨格スライスの明示 placeholder）。
renderPage(
    '<div class="card"><p class="installer-progress">ステップ '
    . $flow->position($stepId) . ' / ' . $flow->count() . '</p>'
    . '<h2>' . Html::escape($stepId) . '</h2>'
    . '<p>このステップは次のスライス（S3-2）で配線されます。</p>'
    . '<p><a href="?step=requirements">← 要件チェックへ戻る</a></p></div>',
);
