<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class FieldDefServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                FieldDefRepositoryInterface::class,
                static function (ContainerInterface $c): FieldDefRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoFieldDefRepository($query, $orgId);
                },
            )
            ->set(
                GetFieldDefByIdUseCaseInterface::class,
                static function (ContainerInterface $c): GetFieldDefByIdUseCaseInterface {
                    $repository = $c->get(FieldDefRepositoryInterface::class);

                    if (!$repository instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new GetFieldDefByIdUseCase($repository);
                },
            )
            ->set(
                GetFieldDefByIdHandler::class,
                static function (ContainerInterface $c): GetFieldDefByIdHandler {
                    $useCase = $c->get(GetFieldDefByIdUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetFieldDefByIdUseCaseInterface) {
                        throw new LogicException('GetFieldDefById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetFieldDefByIdHandler($useCase, $response);
                },
            )
            ->set(
                CreateFieldDefUseCaseInterface::class,
                static function (ContainerInterface $c): CreateFieldDefUseCaseInterface {
                    $fieldDefs = $c->get(FieldDefRepositoryInterface::class);
                    $entityTypes = $c->get(EntityTypeRepositoryInterface::class);

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    return new CreateFieldDefUseCase($fieldDefs, $entityTypes);
                },
            )
            ->set(
                CreateFieldDefHandler::class,
                static function (ContainerInterface $c): CreateFieldDefHandler {
                    $useCase = $c->get(CreateFieldDefUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateFieldDefUseCaseInterface) {
                        throw new LogicException('CreateFieldDef use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateFieldDefHandler($useCase, $response);
                },
            )
            ->set(
                DeleteFieldDefUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteFieldDefUseCaseInterface {
                    $repository = $c->get(FieldDefRepositoryInterface::class);

                    if (!$repository instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new DeleteFieldDefUseCase($repository);
                },
            )
            ->set(
                DeleteFieldDefHandler::class,
                static function (ContainerInterface $c): DeleteFieldDefHandler {
                    $useCase = $c->get(DeleteFieldDefUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteFieldDefUseCaseInterface) {
                        throw new LogicException('DeleteFieldDef use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteFieldDefHandler($useCase, $responseFactory);
                },
            )
            ->set(
                ListFieldDefsUseCaseInterface::class,
                static function (ContainerInterface $c): ListFieldDefsUseCaseInterface {
                    $repository = $c->get(FieldDefRepositoryInterface::class);

                    if (!$repository instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new ListFieldDefsUseCase($repository);
                },
            )
            ->set(
                ListFieldDefsHandler::class,
                static function (ContainerInterface $c): ListFieldDefsHandler {
                    $useCase = $c->get(ListFieldDefsUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListFieldDefsUseCaseInterface) {
                        throw new LogicException('ListFieldDefs use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListFieldDefsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateFieldDefUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateFieldDefUseCaseInterface {
                    $fieldDefs = $c->get(FieldDefRepositoryInterface::class);
                    $entityTypes = $c->get(EntityTypeRepositoryInterface::class);

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    return new UpdateFieldDefUseCase($fieldDefs, $entityTypes);
                },
            )
            ->set(
                UpdateFieldDefHandler::class,
                static function (ContainerInterface $c): UpdateFieldDefHandler {
                    $useCase = $c->get(UpdateFieldDefUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateFieldDefUseCaseInterface) {
                        throw new LogicException('UpdateFieldDef use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateFieldDefHandler($useCase, $response);
                },
            )
            ->set(
                FieldDefNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): FieldDefNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new FieldDefNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                FieldDefConflictExceptionHandler::class,
                static function (ContainerInterface $c): FieldDefConflictExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new FieldDefConflictExceptionHandler($problemDetails);
                },
            )
            ->set(
                FieldKeyNotRegisteredExceptionHandler::class,
                static function (ContainerInterface $c): FieldKeyNotRegisteredExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new FieldKeyNotRegisteredExceptionHandler($problemDetails);
                },
            )
            ->set(
                FieldTypeMismatchExceptionHandler::class,
                static function (ContainerInterface $c): FieldTypeMismatchExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new FieldTypeMismatchExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.field_def',
                static function (ContainerInterface $c): FieldDefRouteRegistrar {
                    $get = $c->get(GetFieldDefByIdHandler::class);
                    $create = $c->get(CreateFieldDefHandler::class);
                    $update = $c->get(UpdateFieldDefHandler::class);
                    $delete = $c->get(DeleteFieldDefHandler::class);
                    $list = $c->get(ListFieldDefsHandler::class);

                    if (!$get instanceof GetFieldDefByIdHandler) {
                        throw new LogicException('GetFieldDefById handler service is invalid.');
                    }

                    if (!$create instanceof CreateFieldDefHandler) {
                        throw new LogicException('CreateFieldDef handler service is invalid.');
                    }

                    if (!$update instanceof UpdateFieldDefHandler) {
                        throw new LogicException('UpdateFieldDef handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteFieldDefHandler) {
                        throw new LogicException('DeleteFieldDef handler service is invalid.');
                    }

                    if (!$list instanceof ListFieldDefsHandler) {
                        throw new LogicException('ListFieldDefs handler service is invalid.');
                    }

                    return new FieldDefRouteRegistrar($get, $create, $update, $delete, $list);
                },
            );
    }
}
