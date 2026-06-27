<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
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
            )
            ->set(
                ImportRedirectsCsvUseCase::class,
                static function (ContainerInterface $c): ImportRedirectsCsvUseCase {
                    $redirects = $c->get(UrlRedirectRepositoryInterface::class);

                    if (!$redirects instanceof UrlRedirectRepositoryInterface) {
                        throw new LogicException('URL redirect repository service is invalid.');
                    }

                    return new ImportRedirectsCsvUseCase($redirects);
                },
            )
            ->set(
                ImportRedirectsCsvHttpHandler::class,
                static function (ContainerInterface $c): ImportRedirectsCsvHttpHandler {
                    $useCase = $c->get(ImportRedirectsCsvUseCase::class);
                    $json = $c->get(JsonResponseFactory::class);
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$useCase instanceof ImportRedirectsCsvUseCase) {
                        throw new LogicException('Import redirects CSV use case service is invalid.');
                    }
                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }
                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new ImportRedirectsCsvHttpHandler($useCase, $json, $problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.url_redirect',
                static function (ContainerInterface $c): UrlRedirectRouteRegistrar {
                    $handler = $c->get(ImportRedirectsCsvHttpHandler::class);

                    if (!$handler instanceof ImportRedirectsCsvHttpHandler) {
                        throw new LogicException('Import redirects CSV handler service is invalid.');
                    }

                    return new UrlRedirectRouteRegistrar($handler);
                },
            );
    }
}
