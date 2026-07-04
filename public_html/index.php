<?php

declare(strict_types=1);

use Nene2\Http\ResponseEmitter;
use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Http\SingleOriginKernel;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require dirname(__DIR__) . '/vendor/autoload.php';

// 共有ホスティングブリッジ（#707）: phpdotenv v5 は .env を $_ENV/$_SERVER にしか
// 書かず putenv しないため、getenv() 直読みのプロバイダ設定（APP_BASE_PATH /
// BASE_DOMAIN / ORG_SLUG / TENANT_RESOLUTION / MEDIA_STORAGE_DRIVER）が「.env しか
// 無い」Tier A 設置で全て既定値に落ちる。ここで .env を一度読み、実環境変数が
// 無いキーだけ putenv で写す（実環境変数が優先＝Docker 系デプロイは挙動不変）。
if (is_file(dirname(__DIR__) . '/.env')) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

    foreach ($_ENV as $key => $value) {
        if (is_string($value) && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();
$psr17Factory = $container->get(Psr17Factory::class);
assert($psr17Factory instanceof Psr17Factory);
$serverRequestCreator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
);

$request = $serverRequestCreator->fromGlobals();

// サブディレクトリ設置（APP_BASE_PATH）の prefix strip（#707）: ルーティングは
// 常にルート相対で、managed 環境では Caddy `handle_path` が prefix を剥がすが、
// 共有ホスティングにはその層が無い。ここで剥がしてからアプリに渡す（URL 生成側は
// BasePath が APP_BASE_PATH を前置する — 入口 strip と対で成立する）。ルート設置
// （APP_BASE_PATH 空）では完全 no-op。
$appBasePath = NeNeRecords\Http\BasePath::fromEnv();

if ($appBasePath !== '') {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if ($path === $appBasePath || str_starts_with($path, $appBasePath . '/')) {
        $stripped = substr($path, strlen($appBasePath));
        $request = $request->withUri($uri->withPath($stripped === '' ? '/' : $stripped));
    }
}

// SingleOriginKernel wraps the NENE2 application pipeline with the single-origin
// edge layers (per-org 301 redirect map, then SPA shell fallback). See the kernel.
$kernel = $container->get(SingleOriginKernel::class);
assert($kernel instanceof SingleOriginKernel);
$response = $kernel->handle($request);

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);
$emitter->emit($response);
