<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class EntityRelationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                EntityRelationRepositoryInterface::class,
                static function (ContainerInterface $c): EntityRelationRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoEntityRelationRepository($query);
                },
            )
            ->set(
                ListEntityRelationsUseCaseInterface::class,
                static function (ContainerInterface $c): ListEntityRelationsUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $fieldDefs = $c->get(FieldDefRepositoryInterface::class);
                    $entityRelations = $c->get(EntityRelationRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field def repository service is invalid.');
                    }

                    if (!$entityRelations instanceof EntityRelationRepositoryInterface) {
                        throw new LogicException('Entity relation repository service is invalid.');
                    }

                    return new ListEntityRelationsUseCase($entities, $fieldDefs, $entityRelations);
                },
            )
            ->set(
                ListEntityRelationsHandler::class,
                static function (ContainerInterface $c): ListEntityRelationsHandler {
                    $useCase = $c->get(ListEntityRelationsUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListEntityRelationsUseCaseInterface) {
                        throw new LogicException('ListEntityRelations use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListEntityRelationsHandler($useCase, $response);
                },
            )
            ->set(
                AttachEntityRelationUseCaseInterface::class,
                static function (ContainerInterface $c): AttachEntityRelationUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $fieldDefs = $c->get(FieldDefRepositoryInterface::class);
                    $entityRelations = $c->get(EntityRelationRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field def repository service is invalid.');
                    }

                    if (!$entityRelations instanceof EntityRelationRepositoryInterface) {
                        throw new LogicException('Entity relation repository service is invalid.');
                    }

                    return new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);
                },
            )
            ->set(
                AttachEntityRelationHandler::class,
                static function (ContainerInterface $c): AttachEntityRelationHandler {
                    $useCase = $c->get(AttachEntityRelationUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof AttachEntityRelationUseCaseInterface) {
                        throw new LogicException('AttachEntityRelation use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new AttachEntityRelationHandler($useCase, $response);
                },
            )
            ->set(
                DetachEntityRelationUseCaseInterface::class,
                static function (ContainerInterface $c): DetachEntityRelationUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $entityRelations = $c->get(EntityRelationRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$entityRelations instanceof EntityRelationRepositoryInterface) {
                        throw new LogicException('Entity relation repository service is invalid.');
                    }

                    return new DetachEntityRelationUseCase($entities, $entityRelations);
                },
            )
            ->set(
                DetachEntityRelationHandler::class,
                static function (ContainerInterface $c): DetachEntityRelationHandler {
                    $useCase = $c->get(DetachEntityRelationUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DetachEntityRelationUseCaseInterface) {
                        throw new LogicException('DetachEntityRelation use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DetachEntityRelationHandler($useCase, $responseFactory);
                },
            )
            ->set(
                RelationTargetTypeMismatchExceptionHandler::class,
                static function (ContainerInterface $c): RelationTargetTypeMismatchExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new RelationTargetTypeMismatchExceptionHandler($problemDetails);
                },
            )
            ->set(
                RelationAlreadyAttachedExceptionHandler::class,
                static function (ContainerInterface $c): RelationAlreadyAttachedExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new RelationAlreadyAttachedExceptionHandler($problemDetails);
                },
            )
            ->set(
                RelationNotAttachedExceptionHandler::class,
                static function (ContainerInterface $c): RelationNotAttachedExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new RelationNotAttachedExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.entity_relation',
                static function (ContainerInterface $c): EntityRelationRouteRegistrar {
                    $list = $c->get(ListEntityRelationsHandler::class);
                    $attach = $c->get(AttachEntityRelationHandler::class);
                    $detach = $c->get(DetachEntityRelationHandler::class);

                    if (!$list instanceof ListEntityRelationsHandler) {
                        throw new LogicException('ListEntityRelations handler service is invalid.');
                    }

                    if (!$attach instanceof AttachEntityRelationHandler) {
                        throw new LogicException('AttachEntityRelation handler service is invalid.');
                    }

                    if (!$detach instanceof DetachEntityRelationHandler) {
                        throw new LogicException('DetachEntityRelation handler service is invalid.');
                    }

                    return new EntityRelationRouteRegistrar($list, $attach, $detach);
                },
            );
    }
}
