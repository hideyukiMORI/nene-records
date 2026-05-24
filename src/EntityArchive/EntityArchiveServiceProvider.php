<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class EntityArchiveServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                EntityArchiveRepositoryInterface::class,
                static function (ContainerInterface $c): EntityArchiveRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoEntityArchiveRepository($query);
                },
            )
            ->set(
                GetEntityArchiveCsvHandler::class,
                static function (ContainerInterface $c): GetEntityArchiveCsvHandler {
                    $archive = $c->get(EntityArchiveRepositoryInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$archive instanceof EntityArchiveRepositoryInterface) {
                        throw new LogicException('Entity archive repository service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new GetEntityArchiveCsvHandler($archive, $responseFactory);
                },
            )
            ->set(
                'nene-records.route_registrar.entity_archive',
                static function (ContainerInterface $c): EntityArchiveRouteRegistrar {
                    $csv = $c->get(GetEntityArchiveCsvHandler::class);

                    if (!$csv instanceof GetEntityArchiveCsvHandler) {
                        throw new \LogicException('GetEntityArchiveCsv handler service is invalid.');
                    }

                    return new EntityArchiveRouteRegistrar($csv);
                },
            );
    }
}
