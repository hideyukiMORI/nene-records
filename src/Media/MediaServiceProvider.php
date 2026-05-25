<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Http\RuntimeServiceProvider;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class MediaServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                MediaRepositoryInterface::class,
                static function (ContainerInterface $c): MediaRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoMediaRepository($query);
                },
            )
            ->set(
                UploadMediaUseCaseInterface::class,
                static function (ContainerInterface $c): UploadMediaUseCaseInterface {
                    $repository = $c->get(MediaRepositoryInterface::class);
                    $projectRoot = $c->get(RuntimeServiceProvider::PROJECT_ROOT);

                    if (!$repository instanceof MediaRepositoryInterface) {
                        throw new LogicException('Media repository service is invalid.');
                    }

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    $storageRoot = $projectRoot . '/var/media';

                    return new UploadMediaUseCase($repository, $storageRoot);
                },
            )
            ->set(
                UploadMediaHandler::class,
                static function (ContainerInterface $c): UploadMediaHandler {
                    $useCase = $c->get(UploadMediaUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UploadMediaUseCaseInterface) {
                        throw new LogicException('UploadMedia use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UploadMediaHandler($useCase, $response);
                },
            )
            ->set(
                ServeMediaHandler::class,
                static function (ContainerInterface $c): ServeMediaHandler {
                    $projectRoot = $c->get(RuntimeServiceProvider::PROJECT_ROOT);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $streamFactory = $c->get(StreamFactoryInterface::class);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    $storageRoot = $projectRoot . '/var/media';

                    return new ServeMediaHandler($storageRoot, $responseFactory, $streamFactory);
                },
            )
            ->set(
                MediaInvalidTypeExceptionHandler::class,
                static function (ContainerInterface $c): MediaInvalidTypeExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new MediaInvalidTypeExceptionHandler($problemDetails);
                },
            )
            ->set(
                MediaTooLargeExceptionHandler::class,
                static function (ContainerInterface $c): MediaTooLargeExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new MediaTooLargeExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.media',
                static function (ContainerInterface $c): MediaRouteRegistrar {
                    $upload = $c->get(UploadMediaHandler::class);
                    $serve = $c->get(ServeMediaHandler::class);

                    if (!$upload instanceof UploadMediaHandler) {
                        throw new LogicException('UploadMedia handler service is invalid.');
                    }

                    if (!$serve instanceof ServeMediaHandler) {
                        throw new LogicException('ServeMedia handler service is invalid.');
                    }

                    return new MediaRouteRegistrar($upload, $serve);
                },
            );
    }
}
