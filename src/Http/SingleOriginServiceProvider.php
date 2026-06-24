<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wires the single-origin edge layer: the SPA shell fallback and the outer
 * {@see SingleOriginKernel} that composes the application pipeline with the 301
 * redirect map and shell fallback.
 */
final readonly class SingleOriginServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                SpaShellFallback::class,
                static function (ContainerInterface $c): SpaShellFallback {
                    $projectRoot = $c->get(RuntimeServiceProvider::PROJECT_ROOT);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $streamFactory = $c->get(StreamFactoryInterface::class);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root is not configured.');
                    }
                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }
                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new SpaShellFallback(
                        $projectRoot . '/frontend/dist/index.html',
                        $responseFactory,
                        $streamFactory,
                    );
                },
            )
            ->set(
                SingleOriginKernel::class,
                static function (ContainerInterface $c): SingleOriginKernel {
                    $application = $c->get(RequestHandlerInterface::class);
                    $redirects = $c->get(UrlRedirectResolver::class);
                    $shell = $c->get(SpaShellFallback::class);

                    if (!$application instanceof RequestHandlerInterface) {
                        throw new LogicException('Application request handler service is invalid.');
                    }
                    if (!$redirects instanceof UrlRedirectResolver) {
                        throw new LogicException('URL redirect resolver service is invalid.');
                    }
                    if (!$shell instanceof SpaShellFallback) {
                        throw new LogicException('SPA shell fallback service is invalid.');
                    }

                    return new SingleOriginKernel($application, $redirects, $shell);
                },
            );
    }
}
