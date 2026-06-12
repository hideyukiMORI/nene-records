<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityArchive\EntityArchiveRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class EntityTypeServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                EntityTypeRepositoryInterface::class,
                static function (ContainerInterface $c): EntityTypeRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoEntityTypeRepository($query, $orgId);
                },
            )
            ->set(
                GetEntityTypeByIdUseCaseInterface::class,
                static function (ContainerInterface $c): GetEntityTypeByIdUseCaseInterface {
                    $repository = $c->get(EntityTypeRepositoryInterface::class);

                    if (!$repository instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    return new GetEntityTypeByIdUseCase($repository);
                },
            )
            ->set(
                GetEntityTypeByIdHandler::class,
                static function (ContainerInterface $c): GetEntityTypeByIdHandler {
                    $useCase = $c->get(GetEntityTypeByIdUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetEntityTypeByIdUseCaseInterface) {
                        throw new LogicException('GetEntityTypeById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetEntityTypeByIdHandler($useCase, $response);
                },
            )
            ->set(
                CreateEntityTypeUseCaseInterface::class,
                static function (ContainerInterface $c): CreateEntityTypeUseCaseInterface {
                    $repository = $c->get(EntityTypeRepositoryInterface::class);

                    if (!$repository instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    return new CreateEntityTypeUseCase($repository);
                },
            )
            ->set(
                CreateEntityTypeHandler::class,
                static function (ContainerInterface $c): CreateEntityTypeHandler {
                    $useCase = $c->get(CreateEntityTypeUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateEntityTypeUseCaseInterface) {
                        throw new LogicException('CreateEntityType use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateEntityTypeHandler($useCase, $response);
                },
            )
            ->set(
                DeleteEntityTypeUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteEntityTypeUseCaseInterface {
                    $repository = $c->get(EntityTypeRepositoryInterface::class);
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $archive = $c->get(EntityArchiveRepositoryInterface::class);

                    if (!$repository instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$archive instanceof EntityArchiveRepositoryInterface) {
                        throw new LogicException('Entity archive repository service is invalid.');
                    }

                    return new DeleteEntityTypeUseCase($repository, $entities, $archive);
                },
            )
            ->set(
                DeleteEntityTypeHandler::class,
                static function (ContainerInterface $c): DeleteEntityTypeHandler {
                    $useCase = $c->get(DeleteEntityTypeUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteEntityTypeUseCaseInterface) {
                        throw new LogicException('DeleteEntityType use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteEntityTypeHandler($useCase, $responseFactory);
                },
            )
            ->set(
                ListEntityTypesUseCaseInterface::class,
                static function (ContainerInterface $c): ListEntityTypesUseCaseInterface {
                    $repository = $c->get(EntityTypeRepositoryInterface::class);

                    if (!$repository instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    return new ListEntityTypesUseCase($repository);
                },
            )
            ->set(
                ListEntityTypesHandler::class,
                static function (ContainerInterface $c): ListEntityTypesHandler {
                    $useCase = $c->get(ListEntityTypesUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListEntityTypesUseCaseInterface) {
                        throw new LogicException('ListEntityTypes use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListEntityTypesHandler($useCase, $response);
                },
            )
            ->set(
                UpdateEntityTypeUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateEntityTypeUseCaseInterface {
                    $repository = $c->get(EntityTypeRepositoryInterface::class);

                    if (!$repository instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    return new UpdateEntityTypeUseCase($repository);
                },
            )
            ->set(
                UpdateEntityTypeHandler::class,
                static function (ContainerInterface $c): UpdateEntityTypeHandler {
                    $useCase = $c->get(UpdateEntityTypeUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateEntityTypeUseCaseInterface) {
                        throw new LogicException('UpdateEntityType use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateEntityTypeHandler($useCase, $response);
                },
            )
            ->set(
                ReorderEntityTypesUseCaseInterface::class,
                static function (ContainerInterface $c): ReorderEntityTypesUseCaseInterface {
                    $repository = $c->get(EntityTypeRepositoryInterface::class);

                    if (!$repository instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    return new ReorderEntityTypesUseCase($repository);
                },
            )
            ->set(
                ReorderEntityTypesHandler::class,
                static function (ContainerInterface $c): ReorderEntityTypesHandler {
                    $useCase = $c->get(ReorderEntityTypesUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof ReorderEntityTypesUseCaseInterface) {
                        throw new LogicException('ReorderEntityTypes use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new ReorderEntityTypesHandler($useCase, $responseFactory);
                },
            )
            ->set(
                EntityTypeNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): EntityTypeNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new EntityTypeNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                EntityTypeHasEntitiesExceptionHandler::class,
                static function (ContainerInterface $c): EntityTypeHasEntitiesExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new EntityTypeHasEntitiesExceptionHandler($problemDetails);
                },
            )
            ->set(
                EntityTypeSlugConflictExceptionHandler::class,
                static function (ContainerInterface $c): EntityTypeSlugConflictExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new EntityTypeSlugConflictExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.entity_type',
                static function (ContainerInterface $c): EntityTypeRouteRegistrar {
                    $get = $c->get(GetEntityTypeByIdHandler::class);
                    $create = $c->get(CreateEntityTypeHandler::class);
                    $update = $c->get(UpdateEntityTypeHandler::class);
                    $delete = $c->get(DeleteEntityTypeHandler::class);
                    $list = $c->get(ListEntityTypesHandler::class);
                    $reorder = $c->get(ReorderEntityTypesHandler::class);

                    if (!$get instanceof GetEntityTypeByIdHandler) {
                        throw new LogicException('GetEntityTypeById handler service is invalid.');
                    }

                    if (!$create instanceof CreateEntityTypeHandler) {
                        throw new LogicException('CreateEntityType handler service is invalid.');
                    }

                    if (!$update instanceof UpdateEntityTypeHandler) {
                        throw new LogicException('UpdateEntityType handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteEntityTypeHandler) {
                        throw new LogicException('DeleteEntityType handler service is invalid.');
                    }

                    if (!$list instanceof ListEntityTypesHandler) {
                        throw new LogicException('ListEntityTypes handler service is invalid.');
                    }

                    if (!$reorder instanceof ReorderEntityTypesHandler) {
                        throw new LogicException('ReorderEntityTypes handler service is invalid.');
                    }

                    return new EntityTypeRouteRegistrar($get, $create, $update, $delete, $list, $reorder);
                },
            );
    }
}
