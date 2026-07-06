<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;
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

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    return new PdoEntityArchiveRepository($query, $orgId, $clock);
                },
            )
            ->set(
                GetEntityArchiveCsvUseCaseInterface::class,
                static function (ContainerInterface $c): GetEntityArchiveCsvUseCaseInterface {
                    $archive = $c->get(EntityArchiveRepositoryInterface::class);

                    if (!$archive instanceof EntityArchiveRepositoryInterface) {
                        throw new LogicException('Entity archive repository service is invalid.');
                    }

                    return new GetEntityArchiveCsvUseCase($archive);
                },
            )
            ->set(
                GetEntityArchiveCsvHandler::class,
                static function (ContainerInterface $c): GetEntityArchiveCsvHandler {
                    $useCase         = $c->get(GetEntityArchiveCsvUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof GetEntityArchiveCsvUseCaseInterface) {
                        throw new LogicException('GetEntityArchiveCsvUseCaseInterface service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    return new GetEntityArchiveCsvHandler($useCase, $responseFactory, $clock);
                },
            )
            ->set(
                'nene-records.route_registrar.entity_archive',
                static function (ContainerInterface $c): EntityArchiveRouteRegistrar {
                    $csv = $c->get(GetEntityArchiveCsvHandler::class);

                    if (!$csv instanceof GetEntityArchiveCsvHandler) {
                        throw new LogicException('GetEntityArchiveCsv handler service is invalid.');
                    }

                    return new EntityArchiveRouteRegistrar($csv);
                },
            );
    }
}
