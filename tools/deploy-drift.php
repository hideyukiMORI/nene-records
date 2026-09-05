<?php

declare(strict_types=1);

/**
 * 配備先のツリーが、このリポジトリのどのコミットに対応するかを突き合わせる（#1073 の再発防止）。
 *
 * 上書き配備は差分を残さないので、**配備先で誰かが手で書き換えたファイルは、次の配備で黙って
 * 消える**。消えた事実もどこにも記録されない。2026-09-05 に ayane.co.jp で実際にそうなっており、
 * 気づけたのは施主が「本当にそうか」と問い直したからだった。人の記憶に頼らず配備前に機械が
 * 列挙する、というのがこのツールの役目。
 *
 * 使い方（2段）:
 *
 *   # 1) 配備先で md5 の一覧を作る
 *   ssh <host> 'cd <app-root> && find src templates -type f \( -name "*.php" -o -name "*.html" \) \
 *       | sort | xargs md5sum' > /tmp/deployed.md5
 *
 *   # 2) 手元のリポジトリで突き合わせる
 *   php tools/deploy-drift.php /tmp/deployed.md5
 *
 * 判定は3段階:
 *
 *   🟢 HEAD 一致        … 何もしなくてよい
 *   🟡 別コミット一致    … 古いだけ。配備で前へ進むだけなので失われる物は無い
 *   🔴 どのコミットとも不一致 … **配備先だけに存在する改変**。上書きすると消える。人が見ること
 *
 * 終了コード: 🔴 が 1 件でもあれば 1、無ければ 0。CI や配備スクリプトのゲートに使える。
 */

$manifest = $argv[1] ?? null;
if ($manifest === null || !is_file($manifest)) {
    fwrite(STDERR, "使い方: php tools/deploy-drift.php <md5sum の出力ファイル>\n");
    fwrite(STDERR, "  作り方: ssh <host> 'cd <app-root> && find src templates -type f \\( -name \"*.php\" -o -name \"*.html\" \\) | sort | xargs md5sum'\n");
    exit(2);
}

$root = dirname(__DIR__);

/** @return array<string, string> path => md5 */
function readManifest(string $file): array
{
    $map = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        // md5sum の出力は "<hash>  <path>"（空白2つ）。バイナリモードの "*" 接頭辞も剥がす。
        if (preg_match('/^([0-9a-f]{32})\s+\*?(.+)$/', trim($line), $m) !== 1) {
            continue;
        }
        $map[ltrim($m[2], './')] = $m[1];
    }

    return $map;
}

function git(string $root, string ...$args): string
{
    $cmd = 'git -C ' . escapeshellarg($root);
    foreach ($args as $a) {
        $cmd .= ' ' . escapeshellarg($a);
    }

    return (string) shell_exec($cmd . ' 2>/dev/null');
}

/** blob の md5。存在しなければ null。 */
function blobMd5(string $root, string $ref): ?string
{
    $out = shell_exec('git -C ' . escapeshellarg($root) . ' cat-file blob ' . escapeshellarg($ref) . ' 2>/dev/null');

    return $out === null || $out === false ? null : md5($out);
}

$deployed = readManifest($manifest);
if ($deployed === []) {
    fwrite(STDERR, "中止: マニフェストから1件も読めなかった。md5sum の出力か確認すること。\n");
    exit(2);
}

// HEAD 側の一覧（マニフェストと同じ射程だけ見る）
$headFiles = array_filter(
    explode("\n", trim(git($root, 'ls-tree', '-r', '--name-only', 'HEAD', '--', 'src', 'templates'))),
    static fn (string $p): bool => $p !== '' && (str_ends_with($p, '.php') || str_ends_with($p, '.html')),
);
$headFiles = array_flip($headFiles);

$same = [];
$older = [];
$drift = [];
$missingUpstream = [];   // 配備先にあってリポに無い
$missingDeployed = [];   // リポにあって配備先に無い

foreach ($deployed as $path => $hash) {
    if (!isset($headFiles[$path])) {
        $missingUpstream[] = $path;
        continue;
    }

    if (blobMd5($root, 'HEAD:' . $path) === $hash) {
        $same[] = $path;
        continue;
    }

    // HEAD と違う。履歴のどこかに在るか探す（新しい順に見るので、たいてい数手で当たる）
    $commits = array_filter(explode("\n", trim(git($root, 'log', '--all', '--format=%H', '--', $path))));
    $found = null;
    foreach ($commits as $commit) {
        if (blobMd5($root, $commit . ':' . $path) === $hash) {
            $found = $commit;
            break;
        }
    }

    if ($found === null) {
        $drift[] = $path;
    } else {
        $older[$path] = [substr($found, 0, 8), trim(git($root, 'log', '-1', '--format=%ci', $found))];
    }
}

foreach (array_keys($headFiles) as $path) {
    if (!isset($deployed[$path])) {
        $missingDeployed[] = $path;
    }
}

printf("対象 %d 件（マニフェスト）／HEAD 側 %d 件\n\n", count($deployed), count($headFiles));
printf("🟢 HEAD と一致              %d\n", count($same));
printf("🟡 上流の別コミットと一致    %d\n", count($older));
printf("🔴 どのコミットとも不一致    %d\n", count($drift));
printf("⚪ 配備先にあって上流に無い  %d\n", count($missingUpstream));
printf("⚪ 上流にあって配備先に無い  %d\n", count($missingDeployed));

if ($older !== []) {
    echo "\n🟡 古いだけ（配備で前へ進む。失われる物は無い）\n";
    foreach ($older as $path => [$commit, $date]) {
        printf("   %s  %s  %s\n", $commit, substr($date, 0, 10), $path);
    }
}

if ($missingUpstream !== [] || $missingDeployed !== []) {
    echo "\n⚪ 片側にしか無いファイル\n";
    foreach ($missingUpstream as $p) {
        echo "   配備先のみ: $p\n";
    }
    foreach ($missingDeployed as $p) {
        echo "   上流のみ:   $p\n";
    }
}

if ($drift === []) {
    echo "\n🟢 配備先だけの改変はありません。上書きしても失われる物はありません。\n";
    exit(0);
}

echo "\n🔴 配備先だけに存在する改変 — 上書きすると消えます。中身を見てから配備してください:\n";
foreach ($drift as $path) {
    printf("   %s\n", $path);
}
echo "\n   差分の見方: ssh <host> \"cat <app-root>/<path>\" | diff <(git show HEAD:<path>) -\n";

exit(1);
