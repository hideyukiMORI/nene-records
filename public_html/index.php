<?php

declare(strict_types=1);

use Nene2\Http\ResponseEmitter;
use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Http\SingleOriginKernel;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require dirname(__DIR__) . '/vendor/autoload.php';

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

// SingleOriginKernel wraps the NENE2 application pipeline with the single-origin
// edge layers (per-org 301 redirect map, then SPA shell fallback). See the kernel.
$kernel = $container->get(SingleOriginKernel::class);
assert($kernel instanceof SingleOriginKernel);
$response = $kernel->handle($request);

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);
$emitter->emit($response);
