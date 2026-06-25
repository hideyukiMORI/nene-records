<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\RequestScopedHolder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class UrlRedirectServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                UrlRedirectRepositoryInterface::class,
                static function (ContainerInterface $c): UrlRedirectRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoUrlRedirectRepository($query, $orgId);
                },
            )
            ->set(
                UrlRedirectResolver::class,
                static function (ContainerInterface $c): UrlRedirectResolver {
                    $redirects = $c->get(UrlRedirectRepositoryInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$redirects instanceof UrlRedirectRepositoryInterface) {
                        throw new LogicException('URL redirect repository service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new UrlRedirectResolver(
                        $redirects,
                        $responseFactory,
                        \NeNeRecords\Http\BasePath::fromEnv(),
                    );
                },
            );
    }
}
