<?php

declare(strict_types=1);

use Nene2\Http\ResponseEmitter;
use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Http\SpaShellFallback;
use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;

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
$application = $container->get(RequestHandlerInterface::class);
assert($application instanceof RequestHandlerInterface);
$response = $application->handle($request);

// Single-origin 301s: a migrated old WordPress URL (from the WXR import redirect
// map) takes precedence over the SPA shell fallback. No-op unless the router 404s
// and the path matches a stored redirect for the resolved org.
$redirectResolver = $container->get(UrlRedirectResolver::class);
assert($redirectResolver instanceof UrlRedirectResolver);
$response = $redirectResolver->apply($request, $response);

// Single-origin: serve the built SPA shell for unmatched GET HTML navigations
// (admin/login/search/tag/browse, etc.) so the client router takes over.
$response = (new SpaShellFallback(
    dirname(__DIR__) . '/frontend/dist/index.html',
    $psr17Factory,
    $psr17Factory,
))->apply($request, $response);

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);
$emitter->emit($response);
