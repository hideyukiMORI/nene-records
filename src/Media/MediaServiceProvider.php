<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use AsyncAws\S3\S3Client;
use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
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
                StorageInterface::class,
                static function (ContainerInterface $c): StorageInterface {
                    $projectRoot = $c->get(RuntimeServiceProvider::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    $driver = getenv('MEDIA_STORAGE_DRIVER') ?: 'local';

                    return match ($driver) {
                        'local' => new LocalStorage($projectRoot . '/var/media'),
                        's3' => self::createS3Storage(),
                        default => throw new LogicException('Unsupported media storage driver: ' . $driver),
                    };
                },
            )
            ->set(
                ImageProcessorInterface::class,
                static fn (ContainerInterface $c): ImageProcessorInterface => new GdImageProcessor(),
            )
            ->set(
                MediaRepositoryInterface::class,
                static function (ContainerInterface $c): MediaRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoMediaRepository($query, $orgId);
                },
            )
            ->set(
                UploadMediaUseCaseInterface::class,
                static function (ContainerInterface $c): UploadMediaUseCaseInterface {
                    $repository = $c->get(MediaRepositoryInterface::class);
                    $storage = $c->get(StorageInterface::class);

                    if (!$repository instanceof MediaRepositoryInterface) {
                        throw new LogicException('Media repository service is invalid.');
                    }

                    if (!$storage instanceof StorageInterface) {
                        throw new LogicException('Media storage service is invalid.');
                    }

                    return new UploadMediaUseCase($repository, $storage);
                },
            )
            ->set(
                ListMediaUseCaseInterface::class,
                static function (ContainerInterface $c): ListMediaUseCaseInterface {
                    $repository = $c->get(MediaRepositoryInterface::class);

                    if (!$repository instanceof MediaRepositoryInterface) {
                        throw new LogicException('Media repository service is invalid.');
                    }

                    return new ListMediaUseCase($repository);
                },
            )
            ->set(
                UpdateMediaAltUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateMediaAltUseCaseInterface {
                    $repository = $c->get(MediaRepositoryInterface::class);

                    if (!$repository instanceof MediaRepositoryInterface) {
                        throw new LogicException('Media repository service is invalid.');
                    }

                    return new UpdateMediaAltUseCase($repository);
                },
            )
            ->set(
                FindMediaUsagesUseCaseInterface::class,
                static function (ContainerInterface $c): FindMediaUsagesUseCaseInterface {
                    $repository = $c->get(MediaRepositoryInterface::class);

                    if (!$repository instanceof MediaRepositoryInterface) {
                        throw new LogicException('Media repository service is invalid.');
                    }

                    return new FindMediaUsagesUseCase($repository);
                },
            )
            ->set(
                DeleteMediaUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteMediaUseCaseInterface {
                    $repository = $c->get(MediaRepositoryInterface::class);
                    $storage = $c->get(StorageInterface::class);

                    if (!$repository instanceof MediaRepositoryInterface) {
                        throw new LogicException('Media repository service is invalid.');
                    }

                    if (!$storage instanceof StorageInterface) {
                        throw new LogicException('Media storage service is invalid.');
                    }

                    return new DeleteMediaUseCase($repository, $storage);
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
                ListMediaHandler::class,
                static function (ContainerInterface $c): ListMediaHandler {
                    $useCase = $c->get(ListMediaUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListMediaUseCaseInterface) {
                        throw new LogicException('ListMedia use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListMediaHandler($useCase, $response);
                },
            )
            ->set(
                DeleteMediaHandler::class,
                static function (ContainerInterface $c): DeleteMediaHandler {
                    $useCase = $c->get(DeleteMediaUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteMediaUseCaseInterface) {
                        throw new LogicException('DeleteMedia use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteMediaHandler($useCase, $responseFactory);
                },
            )
            ->set(
                UpdateMediaAltHandler::class,
                static function (ContainerInterface $c): UpdateMediaAltHandler {
                    $useCase = $c->get(UpdateMediaAltUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateMediaAltUseCaseInterface) {
                        throw new LogicException('UpdateMediaAlt use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateMediaAltHandler($useCase, $response);
                },
            )
            ->set(
                ListMediaUsagesHandler::class,
                static function (ContainerInterface $c): ListMediaUsagesHandler {
                    $useCase = $c->get(FindMediaUsagesUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof FindMediaUsagesUseCaseInterface) {
                        throw new LogicException('FindMediaUsages use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListMediaUsagesHandler($useCase, $response);
                },
            )
            ->set(
                ServeMediaHandler::class,
                static function (ContainerInterface $c): ServeMediaHandler {
                    $storage = $c->get(StorageInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $streamFactory = $c->get(StreamFactoryInterface::class);

                    if (!$storage instanceof StorageInterface) {
                        throw new LogicException('Media storage service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new ServeMediaHandler($storage, $responseFactory, $streamFactory);
                },
            )
            ->set(
                ServeDerivativeHandler::class,
                static function (ContainerInterface $c): ServeDerivativeHandler {
                    $storage = $c->get(StorageInterface::class);
                    $processor = $c->get(ImageProcessorInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $streamFactory = $c->get(StreamFactoryInterface::class);

                    if (!$storage instanceof StorageInterface) {
                        throw new LogicException('Media storage service is invalid.');
                    }

                    if (!$processor instanceof ImageProcessorInterface) {
                        throw new LogicException('Image processor service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new ServeDerivativeHandler($storage, $processor, $responseFactory, $streamFactory);
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
                MediaNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): MediaNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new MediaNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                MediaInUseExceptionHandler::class,
                static function (ContainerInterface $c): MediaInUseExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new MediaInUseExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.media',
                static function (ContainerInterface $c): MediaRouteRegistrar {
                    $upload = $c->get(UploadMediaHandler::class);
                    $list = $c->get(ListMediaHandler::class);
                    $delete = $c->get(DeleteMediaHandler::class);
                    $serve = $c->get(ServeMediaHandler::class);
                    $updateAlt = $c->get(UpdateMediaAltHandler::class);
                    $serveDerivative = $c->get(ServeDerivativeHandler::class);
                    $usages = $c->get(ListMediaUsagesHandler::class);

                    if (!$upload instanceof UploadMediaHandler) {
                        throw new LogicException('UploadMedia handler service is invalid.');
                    }

                    if (!$list instanceof ListMediaHandler) {
                        throw new LogicException('ListMedia handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteMediaHandler) {
                        throw new LogicException('DeleteMedia handler service is invalid.');
                    }

                    if (!$serve instanceof ServeMediaHandler) {
                        throw new LogicException('ServeMedia handler service is invalid.');
                    }

                    if (!$updateAlt instanceof UpdateMediaAltHandler) {
                        throw new LogicException('UpdateMediaAlt handler service is invalid.');
                    }

                    if (!$serveDerivative instanceof ServeDerivativeHandler) {
                        throw new LogicException('ServeDerivative handler service is invalid.');
                    }

                    if (!$usages instanceof ListMediaUsagesHandler) {
                        throw new LogicException('ListMediaUsages handler service is invalid.');
                    }

                    return new MediaRouteRegistrar($upload, $list, $delete, $serve, $updateAlt, $serveDerivative, $usages);
                },
            );
    }

    /**
     * Build the S3-compatible driver from MEDIA_S3_* env vars. Supports AWS S3
     * as well as MinIO / Cloudflare R2 via a custom endpoint + path-style.
     */
    private static function createS3Storage(): S3Storage
    {
        $bucket = (string) (getenv('MEDIA_S3_BUCKET') ?: '');
        $publicBaseUrl = (string) (getenv('MEDIA_S3_PUBLIC_BASE_URL') ?: '');

        if ($bucket === '' || $publicBaseUrl === '') {
            throw new LogicException('MEDIA_S3_BUCKET and MEDIA_S3_PUBLIC_BASE_URL are required for the s3 driver.');
        }

        $config = ['region' => (string) (getenv('MEDIA_S3_REGION') ?: 'us-east-1')];

        $endpoint = (string) (getenv('MEDIA_S3_ENDPOINT') ?: '');
        if ($endpoint !== '') {
            $config['endpoint'] = $endpoint;
            $config['pathStyleEndpoint'] = filter_var(getenv('MEDIA_S3_PATH_STYLE'), FILTER_VALIDATE_BOOL) ? 'true' : 'false';
        }

        $accessKey = (string) (getenv('MEDIA_S3_ACCESS_KEY') ?: '');
        if ($accessKey !== '') {
            $config['accessKeyId'] = $accessKey;
            $config['accessKeySecret'] = (string) (getenv('MEDIA_S3_SECRET_KEY') ?: '');
        }

        return new S3Storage(
            new S3Client($config),
            $bucket,
            $publicBaseUrl,
            (string) (getenv('MEDIA_S3_PREFIX') ?: ''),
        );
    }
}
