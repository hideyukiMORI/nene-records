<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Tag\TagRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class EntityTagServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                EntityTagRepositoryInterface::class,
                static function (ContainerInterface $c): EntityTagRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoEntityTagRepository($query);
                },
            )
            ->set(
                ListEntityTagsUseCaseInterface::class,
                static function (ContainerInterface $c): ListEntityTagsUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $entityTags = $c->get(EntityTagRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$entityTags instanceof EntityTagRepositoryInterface) {
                        throw new LogicException('Entity tag repository service is invalid.');
                    }

                    return new ListEntityTagsUseCase($entities, $entityTags);
                },
            )
            ->set(
                ListEntityTagsHandler::class,
                static function (ContainerInterface $c): ListEntityTagsHandler {
                    $useCase = $c->get(ListEntityTagsUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListEntityTagsUseCaseInterface) {
                        throw new LogicException('ListEntityTags use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListEntityTagsHandler($useCase, $response);
                },
            )
            ->set(
                AttachEntityTagUseCaseInterface::class,
                static function (ContainerInterface $c): AttachEntityTagUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $tags = $c->get(TagRepositoryInterface::class);
                    $entityTags = $c->get(EntityTagRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$tags instanceof TagRepositoryInterface) {
                        throw new LogicException('Tag repository service is invalid.');
                    }

                    if (!$entityTags instanceof EntityTagRepositoryInterface) {
                        throw new LogicException('Entity tag repository service is invalid.');
                    }

                    return new AttachEntityTagUseCase($entities, $tags, $entityTags);
                },
            )
            ->set(
                AttachEntityTagHandler::class,
                static function (ContainerInterface $c): AttachEntityTagHandler {
                    $useCase = $c->get(AttachEntityTagUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof AttachEntityTagUseCaseInterface) {
                        throw new LogicException('AttachEntityTag use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new AttachEntityTagHandler($useCase, $response);
                },
            )
            ->set(
                DetachEntityTagUseCaseInterface::class,
                static function (ContainerInterface $c): DetachEntityTagUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $entityTags = $c->get(EntityTagRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$entityTags instanceof EntityTagRepositoryInterface) {
                        throw new LogicException('Entity tag repository service is invalid.');
                    }

                    return new DetachEntityTagUseCase($entities, $entityTags);
                },
            )
            ->set(
                DetachEntityTagHandler::class,
                static function (ContainerInterface $c): DetachEntityTagHandler {
                    $useCase = $c->get(DetachEntityTagUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DetachEntityTagUseCaseInterface) {
                        throw new LogicException('DetachEntityTag use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DetachEntityTagHandler($useCase, $responseFactory);
                },
            )
            ->set(
                EntityTagAlreadyAttachedExceptionHandler::class,
                static function (ContainerInterface $c): EntityTagAlreadyAttachedExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new EntityTagAlreadyAttachedExceptionHandler($problemDetails);
                },
            )
            ->set(
                EntityTagNotAttachedExceptionHandler::class,
                static function (ContainerInterface $c): EntityTagNotAttachedExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new EntityTagNotAttachedExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.entity_tag',
                static function (ContainerInterface $c): EntityTagRouteRegistrar {
                    $list = $c->get(ListEntityTagsHandler::class);
                    $attach = $c->get(AttachEntityTagHandler::class);
                    $detach = $c->get(DetachEntityTagHandler::class);

                    if (!$list instanceof ListEntityTagsHandler) {
                        throw new LogicException('ListEntityTags handler service is invalid.');
                    }

                    if (!$attach instanceof AttachEntityTagHandler) {
                        throw new LogicException('AttachEntityTag handler service is invalid.');
                    }

                    if (!$detach instanceof DetachEntityTagHandler) {
                        throw new LogicException('DetachEntityTag handler service is invalid.');
                    }

                    return new EntityTagRouteRegistrar($list, $attach, $detach);
                },
            );
    }
}
