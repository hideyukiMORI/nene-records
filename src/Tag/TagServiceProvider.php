<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class TagServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                TagRepositoryInterface::class,
                static function (ContainerInterface $c): TagRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoTagRepository($query, $orgId);
                },
            )
            ->set(
                GetTagByIdUseCaseInterface::class,
                static function (ContainerInterface $c): GetTagByIdUseCaseInterface {
                    $repository = $c->get(TagRepositoryInterface::class);

                    if (!$repository instanceof TagRepositoryInterface) {
                        throw new LogicException('Tag repository service is invalid.');
                    }

                    return new GetTagByIdUseCase($repository);
                },
            )
            ->set(
                GetTagByIdHandler::class,
                static function (ContainerInterface $c): GetTagByIdHandler {
                    $useCase = $c->get(GetTagByIdUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetTagByIdUseCaseInterface) {
                        throw new LogicException('GetTagById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetTagByIdHandler($useCase, $response);
                },
            )
            ->set(
                CreateTagUseCaseInterface::class,
                static function (ContainerInterface $c): CreateTagUseCaseInterface {
                    $repository = $c->get(TagRepositoryInterface::class);

                    if (!$repository instanceof TagRepositoryInterface) {
                        throw new LogicException('Tag repository service is invalid.');
                    }

                    return new CreateTagUseCase($repository);
                },
            )
            ->set(
                CreateTagHandler::class,
                static function (ContainerInterface $c): CreateTagHandler {
                    $useCase = $c->get(CreateTagUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateTagUseCaseInterface) {
                        throw new LogicException('CreateTag use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateTagHandler($useCase, $response);
                },
            )
            ->set(
                DeleteTagUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteTagUseCaseInterface {
                    $repository = $c->get(TagRepositoryInterface::class);

                    if (!$repository instanceof TagRepositoryInterface) {
                        throw new LogicException('Tag repository service is invalid.');
                    }

                    return new DeleteTagUseCase($repository);
                },
            )
            ->set(
                DeleteTagHandler::class,
                static function (ContainerInterface $c): DeleteTagHandler {
                    $useCase = $c->get(DeleteTagUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteTagUseCaseInterface) {
                        throw new LogicException('DeleteTag use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteTagHandler($useCase, $responseFactory);
                },
            )
            ->set(
                ListTagsUseCaseInterface::class,
                static function (ContainerInterface $c): ListTagsUseCaseInterface {
                    $repository = $c->get(TagRepositoryInterface::class);

                    if (!$repository instanceof TagRepositoryInterface) {
                        throw new LogicException('Tag repository service is invalid.');
                    }

                    return new ListTagsUseCase($repository);
                },
            )
            ->set(
                ListTagsHandler::class,
                static function (ContainerInterface $c): ListTagsHandler {
                    $useCase = $c->get(ListTagsUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListTagsUseCaseInterface) {
                        throw new LogicException('ListTags use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListTagsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateTagUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateTagUseCaseInterface {
                    $repository = $c->get(TagRepositoryInterface::class);

                    if (!$repository instanceof TagRepositoryInterface) {
                        throw new LogicException('Tag repository service is invalid.');
                    }

                    return new UpdateTagUseCase($repository);
                },
            )
            ->set(
                UpdateTagHandler::class,
                static function (ContainerInterface $c): UpdateTagHandler {
                    $useCase = $c->get(UpdateTagUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateTagUseCaseInterface) {
                        throw new LogicException('UpdateTag use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateTagHandler($useCase, $response);
                },
            )
            ->set(
                TagNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): TagNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new TagNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                TagSlugConflictExceptionHandler::class,
                static function (ContainerInterface $c): TagSlugConflictExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new TagSlugConflictExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.tag',
                static function (ContainerInterface $c): TagRouteRegistrar {
                    $get = $c->get(GetTagByIdHandler::class);
                    $create = $c->get(CreateTagHandler::class);
                    $update = $c->get(UpdateTagHandler::class);
                    $delete = $c->get(DeleteTagHandler::class);
                    $list = $c->get(ListTagsHandler::class);

                    if (!$get instanceof GetTagByIdHandler) {
                        throw new LogicException('GetTagById handler service is invalid.');
                    }

                    if (!$create instanceof CreateTagHandler) {
                        throw new LogicException('CreateTag handler service is invalid.');
                    }

                    if (!$update instanceof UpdateTagHandler) {
                        throw new LogicException('UpdateTag handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteTagHandler) {
                        throw new LogicException('DeleteTag handler service is invalid.');
                    }

                    if (!$list instanceof ListTagsHandler) {
                        throw new LogicException('ListTags handler service is invalid.');
                    }

                    return new TagRouteRegistrar($get, $create, $update, $delete, $list);
                },
            );
    }
}
