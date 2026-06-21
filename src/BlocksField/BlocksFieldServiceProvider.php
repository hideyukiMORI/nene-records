<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class BlocksFieldServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                BlocksFieldRepositoryInterface::class,
                static function (ContainerInterface $container): BlocksFieldRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoBlocksFieldRepository($query, $orgId);
                },
            )
            ->set(
                CreateBlocksFieldUseCaseInterface::class,
                static function (ContainerInterface $container): CreateBlocksFieldUseCaseInterface {
                    $blocksFields = $container->get(BlocksFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$blocksFields instanceof BlocksFieldRepositoryInterface) {
                        throw new LogicException('Blocks field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new CreateBlocksFieldUseCase($blocksFields, $entities, $fieldDefs, new BlocksDocumentValidator());
                },
            )
            ->set(
                CreateBlocksFieldHandler::class,
                static function (ContainerInterface $container): CreateBlocksFieldHandler {
                    $useCase = $container->get(CreateBlocksFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateBlocksFieldUseCaseInterface) {
                        throw new LogicException('CreateBlocksField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateBlocksFieldHandler($useCase, $response);
                },
            )
            ->set(
                DeleteBlocksFieldUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteBlocksFieldUseCaseInterface {
                    $repository = $container->get(BlocksFieldRepositoryInterface::class);

                    if (!$repository instanceof BlocksFieldRepositoryInterface) {
                        throw new LogicException('Blocks field repository service is invalid.');
                    }

                    return new DeleteBlocksFieldUseCase($repository);
                },
            )
            ->set(
                DeleteBlocksFieldHandler::class,
                static function (ContainerInterface $container): DeleteBlocksFieldHandler {
                    $useCase = $container->get(DeleteBlocksFieldUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteBlocksFieldUseCaseInterface) {
                        throw new LogicException('DeleteBlocksField use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteBlocksFieldHandler($useCase, $responseFactory);
                },
            )
            ->set(
                GetBlocksFieldByIdUseCaseInterface::class,
                static function (ContainerInterface $container): GetBlocksFieldByIdUseCaseInterface {
                    $repository = $container->get(BlocksFieldRepositoryInterface::class);

                    if (!$repository instanceof BlocksFieldRepositoryInterface) {
                        throw new LogicException('Blocks field repository service is invalid.');
                    }

                    return new GetBlocksFieldByIdUseCase($repository);
                },
            )
            ->set(
                GetBlocksFieldByIdHandler::class,
                static function (ContainerInterface $container): GetBlocksFieldByIdHandler {
                    $useCase = $container->get(GetBlocksFieldByIdUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetBlocksFieldByIdUseCaseInterface) {
                        throw new LogicException('GetBlocksFieldById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetBlocksFieldByIdHandler($useCase, $response);
                },
            )
            ->set(
                ListBlocksFieldsUseCaseInterface::class,
                static function (ContainerInterface $container): ListBlocksFieldsUseCaseInterface {
                    $repository = $container->get(BlocksFieldRepositoryInterface::class);

                    if (!$repository instanceof BlocksFieldRepositoryInterface) {
                        throw new LogicException('Blocks field repository service is invalid.');
                    }

                    return new ListBlocksFieldsUseCase($repository);
                },
            )
            ->set(
                ListBlocksFieldsHandler::class,
                static function (ContainerInterface $container): ListBlocksFieldsHandler {
                    $useCase = $container->get(ListBlocksFieldsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListBlocksFieldsUseCaseInterface) {
                        throw new LogicException('ListBlocksFields use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListBlocksFieldsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateBlocksFieldUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateBlocksFieldUseCaseInterface {
                    $blocksFields = $container->get(BlocksFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$blocksFields instanceof BlocksFieldRepositoryInterface) {
                        throw new LogicException('Blocks field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new UpdateBlocksFieldUseCase($blocksFields, $entities, $fieldDefs, new BlocksDocumentValidator());
                },
            )
            ->set(
                UpdateBlocksFieldHandler::class,
                static function (ContainerInterface $container): UpdateBlocksFieldHandler {
                    $useCase = $container->get(UpdateBlocksFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateBlocksFieldUseCaseInterface) {
                        throw new LogicException('UpdateBlocksField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateBlocksFieldHandler($useCase, $response);
                },
            )
            ->set(
                BlocksFieldNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): BlocksFieldNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new BlocksFieldNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.blocks_field',
                static function (ContainerInterface $container): BlocksFieldRouteRegistrar {
                    $list = $container->get(ListBlocksFieldsHandler::class);
                    $get = $container->get(GetBlocksFieldByIdHandler::class);
                    $create = $container->get(CreateBlocksFieldHandler::class);
                    $update = $container->get(UpdateBlocksFieldHandler::class);
                    $delete = $container->get(DeleteBlocksFieldHandler::class);

                    if (!$list instanceof ListBlocksFieldsHandler) {
                        throw new LogicException('ListBlocksFields handler service is invalid.');
                    }

                    if (!$get instanceof GetBlocksFieldByIdHandler) {
                        throw new LogicException('GetBlocksFieldById handler service is invalid.');
                    }

                    if (!$create instanceof CreateBlocksFieldHandler) {
                        throw new LogicException('CreateBlocksField handler service is invalid.');
                    }

                    if (!$update instanceof UpdateBlocksFieldHandler) {
                        throw new LogicException('UpdateBlocksField handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteBlocksFieldHandler) {
                        throw new LogicException('DeleteBlocksField handler service is invalid.');
                    }

                    return new BlocksFieldRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
