<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

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

final readonly class BoolFieldServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                BoolFieldRepositoryInterface::class,
                static function (ContainerInterface $container): BoolFieldRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoBoolFieldRepository($query, $orgId);
                },
            )
            ->set(
                CreateBoolFieldUseCaseInterface::class,
                static function (ContainerInterface $container): CreateBoolFieldUseCaseInterface {
                    $boolFields = $container->get(BoolFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$boolFields instanceof BoolFieldRepositoryInterface) {
                        throw new LogicException('bool field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new CreateBoolFieldUseCase($boolFields, $entities, $fieldDefs);
                },
            )
            ->set(
                CreateBoolFieldHandler::class,
                static function (ContainerInterface $container): CreateBoolFieldHandler {
                    $useCase = $container->get(CreateBoolFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateBoolFieldUseCaseInterface) {
                        throw new LogicException('CreateBoolField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateBoolFieldHandler($useCase, $response);
                },
            )
            ->set(
                DeleteBoolFieldUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteBoolFieldUseCaseInterface {
                    $repository = $container->get(BoolFieldRepositoryInterface::class);

                    if (!$repository instanceof BoolFieldRepositoryInterface) {
                        throw new LogicException('bool field repository service is invalid.');
                    }

                    return new DeleteBoolFieldUseCase($repository);
                },
            )
            ->set(
                DeleteBoolFieldHandler::class,
                static function (ContainerInterface $container): DeleteBoolFieldHandler {
                    $useCase = $container->get(DeleteBoolFieldUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteBoolFieldUseCaseInterface) {
                        throw new LogicException('DeleteBoolField use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteBoolFieldHandler($useCase, $responseFactory);
                },
            )
            ->set(
                GetBoolFieldByIdUseCaseInterface::class,
                static function (ContainerInterface $container): GetBoolFieldByIdUseCaseInterface {
                    $repository = $container->get(BoolFieldRepositoryInterface::class);

                    if (!$repository instanceof BoolFieldRepositoryInterface) {
                        throw new LogicException('bool field repository service is invalid.');
                    }

                    return new GetBoolFieldByIdUseCase($repository);
                },
            )
            ->set(
                GetBoolFieldByIdHandler::class,
                static function (ContainerInterface $container): GetBoolFieldByIdHandler {
                    $useCase = $container->get(GetBoolFieldByIdUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetBoolFieldByIdUseCaseInterface) {
                        throw new LogicException('GetBoolFieldById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetBoolFieldByIdHandler($useCase, $response);
                },
            )
            ->set(
                ListBoolFieldsUseCaseInterface::class,
                static function (ContainerInterface $container): ListBoolFieldsUseCaseInterface {
                    $repository = $container->get(BoolFieldRepositoryInterface::class);

                    if (!$repository instanceof BoolFieldRepositoryInterface) {
                        throw new LogicException('bool field repository service is invalid.');
                    }

                    return new ListBoolFieldsUseCase($repository);
                },
            )
            ->set(
                ListBoolFieldsHandler::class,
                static function (ContainerInterface $container): ListBoolFieldsHandler {
                    $useCase = $container->get(ListBoolFieldsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListBoolFieldsUseCaseInterface) {
                        throw new LogicException('ListBoolFields use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListBoolFieldsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateBoolFieldUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateBoolFieldUseCaseInterface {
                    $boolFields = $container->get(BoolFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$boolFields instanceof BoolFieldRepositoryInterface) {
                        throw new LogicException('bool field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new UpdateBoolFieldUseCase($boolFields, $entities, $fieldDefs);
                },
            )
            ->set(
                UpdateBoolFieldHandler::class,
                static function (ContainerInterface $container): UpdateBoolFieldHandler {
                    $useCase = $container->get(UpdateBoolFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateBoolFieldUseCaseInterface) {
                        throw new LogicException('UpdateBoolField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateBoolFieldHandler($useCase, $response);
                },
            )
            ->set(
                BoolFieldNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): BoolFieldNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new BoolFieldNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.bool_field',
                static function (ContainerInterface $container): BoolFieldRouteRegistrar {
                    $list = $container->get(ListBoolFieldsHandler::class);
                    $get = $container->get(GetBoolFieldByIdHandler::class);
                    $create = $container->get(CreateBoolFieldHandler::class);
                    $update = $container->get(UpdateBoolFieldHandler::class);
                    $delete = $container->get(DeleteBoolFieldHandler::class);

                    if (!$list instanceof ListBoolFieldsHandler) {
                        throw new LogicException('ListBoolFields handler service is invalid.');
                    }

                    if (!$get instanceof GetBoolFieldByIdHandler) {
                        throw new LogicException('GetBoolFieldById handler service is invalid.');
                    }

                    if (!$create instanceof CreateBoolFieldHandler) {
                        throw new LogicException('CreateBoolField handler service is invalid.');
                    }

                    if (!$update instanceof UpdateBoolFieldHandler) {
                        throw new LogicException('UpdateBoolField handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteBoolFieldHandler) {
                        throw new LogicException('DeleteBoolField handler service is invalid.');
                    }

                    return new BoolFieldRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
