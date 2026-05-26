<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class CommentServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                CommentRepositoryInterface::class,
                static function (ContainerInterface $c): CommentRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoCommentRepository($query);
                },
            )
            ->set(
                PostCommentUseCaseInterface::class,
                static function (ContainerInterface $c): PostCommentUseCaseInterface {
                    $repo = $c->get(CommentRepositoryInterface::class);
                    if (!$repo instanceof CommentRepositoryInterface) {
                        throw new LogicException('Comment repository service is invalid.');
                    }

                    return new PostCommentUseCase($repo);
                },
            )
            ->set(
                ListCommentsUseCaseInterface::class,
                static function (ContainerInterface $c): ListCommentsUseCaseInterface {
                    $repo = $c->get(CommentRepositoryInterface::class);
                    if (!$repo instanceof CommentRepositoryInterface) {
                        throw new LogicException('Comment repository service is invalid.');
                    }

                    return new ListCommentsUseCase($repo);
                },
            )
            ->set(
                ListAllCommentsUseCaseInterface::class,
                static function (ContainerInterface $c): ListAllCommentsUseCaseInterface {
                    $repo = $c->get(CommentRepositoryInterface::class);
                    if (!$repo instanceof CommentRepositoryInterface) {
                        throw new LogicException('Comment repository service is invalid.');
                    }

                    return new ListAllCommentsUseCase($repo);
                },
            )
            ->set(
                ApproveCommentUseCaseInterface::class,
                static function (ContainerInterface $c): ApproveCommentUseCaseInterface {
                    $repo = $c->get(CommentRepositoryInterface::class);
                    if (!$repo instanceof CommentRepositoryInterface) {
                        throw new LogicException('Comment repository service is invalid.');
                    }

                    return new ApproveCommentUseCase($repo);
                },
            )
            ->set(
                DeleteCommentUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteCommentUseCaseInterface {
                    $repo = $c->get(CommentRepositoryInterface::class);
                    if (!$repo instanceof CommentRepositoryInterface) {
                        throw new LogicException('Comment repository service is invalid.');
                    }

                    return new DeleteCommentUseCase($repo);
                },
            )
            ->set(
                PostCommentHandler::class,
                static function (ContainerInterface $c): PostCommentHandler {
                    $useCase = $c->get(PostCommentUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof PostCommentUseCaseInterface) {
                        throw new LogicException('PostComment use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new PostCommentHandler($useCase, $response);
                },
            )
            ->set(
                ListCommentsHandler::class,
                static function (ContainerInterface $c): ListCommentsHandler {
                    $useCase = $c->get(ListCommentsUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof ListCommentsUseCaseInterface) {
                        throw new LogicException('ListComments use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new ListCommentsHandler($useCase, $response);
                },
            )
            ->set(
                ListAllCommentsHandler::class,
                static function (ContainerInterface $c): ListAllCommentsHandler {
                    $useCase = $c->get(ListAllCommentsUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof ListAllCommentsUseCaseInterface) {
                        throw new LogicException('ListAllComments use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new ListAllCommentsHandler($useCase, $response);
                },
            )
            ->set(
                ApproveCommentHandler::class,
                static function (ContainerInterface $c): ApproveCommentHandler {
                    $useCase = $c->get(ApproveCommentUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);
                    if (!$useCase instanceof ApproveCommentUseCaseInterface) {
                        throw new LogicException('ApproveComment use case service is invalid.');
                    }
                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new ApproveCommentHandler($useCase, $response);
                },
            )
            ->set(
                DeleteCommentHandler::class,
                static function (ContainerInterface $c): DeleteCommentHandler {
                    $useCase = $c->get(DeleteCommentUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    if (!$useCase instanceof DeleteCommentUseCaseInterface) {
                        throw new LogicException('DeleteComment use case service is invalid.');
                    }
                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactory service is invalid.');
                    }

                    return new DeleteCommentHandler($useCase, $responseFactory);
                },
            )
            ->set(
                CommentRouteRegistrar::class,
                static function (ContainerInterface $c): CommentRouteRegistrar {
                    return new CommentRouteRegistrar(
                        $c->get(PostCommentHandler::class),
                        $c->get(ListCommentsHandler::class),
                        $c->get(ListAllCommentsHandler::class),
                        $c->get(ApproveCommentHandler::class),
                        $c->get(DeleteCommentHandler::class),
                    );
                },
            )
            ->set(
                CommentNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): CommentNotFoundExceptionHandler {
                    $factory = $c->get(ProblemDetailsResponseFactory::class);
                    if (!$factory instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new CommentNotFoundExceptionHandler($factory);
                },
            );
    }
}
