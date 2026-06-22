<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

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

final readonly class EnumFieldServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                EnumFieldRepositoryInterface::class,
                static function (ContainerInterface $container): EnumFieldRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoEnumFieldRepository($query, $orgId);
                },
            )
            ->set(
                CreateEnumFieldUseCaseInterface::class,
                static function (ContainerInterface $container): CreateEnumFieldUseCaseInterface {
                    $enumFields = $container->get(EnumFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$enumFields instanceof EnumFieldRepositoryInterface) {
                        throw new LogicException('enum field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new CreateEnumFieldUseCase($enumFields, $entities, $fieldDefs);
                },
            )
            ->set(
                CreateEnumFieldHandler::class,
                static function (ContainerInterface $container): CreateEnumFieldHandler {
                    $useCase = $container->get(CreateEnumFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateEnumFieldUseCaseInterface) {
                        throw new LogicException('CreateEnumField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateEnumFieldHandler($useCase, $response);
                },
            )
            ->set(
                DeleteEnumFieldUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteEnumFieldUseCaseInterface {
                    $repository = $container->get(EnumFieldRepositoryInterface::class);

                    if (!$repository instanceof EnumFieldRepositoryInterface) {
                        throw new LogicException('enum field repository service is invalid.');
                    }

                    return new DeleteEnumFieldUseCase($repository);
                },
            )
            ->set(
                DeleteEnumFieldHandler::class,
                static function (ContainerInterface $container): DeleteEnumFieldHandler {
                    $useCase = $container->get(DeleteEnumFieldUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteEnumFieldUseCaseInterface) {
                        throw new LogicException('DeleteEnumField use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteEnumFieldHandler($useCase, $responseFactory);
                },
            )
            ->set(
                GetEnumFieldByIdUseCaseInterface::class,
                static function (ContainerInterface $container): GetEnumFieldByIdUseCaseInterface {
                    $repository = $container->get(EnumFieldRepositoryInterface::class);

                    if (!$repository instanceof EnumFieldRepositoryInterface) {
                        throw new LogicException('enum field repository service is invalid.');
                    }

                    return new GetEnumFieldByIdUseCase($repository);
                },
            )
            ->set(
                GetEnumFieldByIdHandler::class,
                static function (ContainerInterface $container): GetEnumFieldByIdHandler {
                    $useCase = $container->get(GetEnumFieldByIdUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetEnumFieldByIdUseCaseInterface) {
                        throw new LogicException('GetEnumFieldById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetEnumFieldByIdHandler($useCase, $response);
                },
            )
            ->set(
                ListEnumFieldsUseCaseInterface::class,
                static function (ContainerInterface $container): ListEnumFieldsUseCaseInterface {
                    $repository = $container->get(EnumFieldRepositoryInterface::class);

                    if (!$repository instanceof EnumFieldRepositoryInterface) {
                        throw new LogicException('enum field repository service is invalid.');
                    }

                    return new ListEnumFieldsUseCase($repository);
                },
            )
            ->set(
                ListEnumFieldsHandler::class,
                static function (ContainerInterface $container): ListEnumFieldsHandler {
                    $useCase = $container->get(ListEnumFieldsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListEnumFieldsUseCaseInterface) {
                        throw new LogicException('ListEnumFields use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListEnumFieldsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateEnumFieldUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateEnumFieldUseCaseInterface {
                    $enumFields = $container->get(EnumFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$enumFields instanceof EnumFieldRepositoryInterface) {
                        throw new LogicException('enum field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new UpdateEnumFieldUseCase($enumFields, $entities, $fieldDefs);
                },
            )
            ->set(
                UpdateEnumFieldHandler::class,
                static function (ContainerInterface $container): UpdateEnumFieldHandler {
                    $useCase = $container->get(UpdateEnumFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateEnumFieldUseCaseInterface) {
                        throw new LogicException('UpdateEnumField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateEnumFieldHandler($useCase, $response);
                },
            )
            ->set(
                EnumFieldNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): EnumFieldNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new EnumFieldNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.enum_field',
                static function (ContainerInterface $container): EnumFieldRouteRegistrar {
                    $list = $container->get(ListEnumFieldsHandler::class);
                    $get = $container->get(GetEnumFieldByIdHandler::class);
                    $create = $container->get(CreateEnumFieldHandler::class);
                    $update = $container->get(UpdateEnumFieldHandler::class);
                    $delete = $container->get(DeleteEnumFieldHandler::class);

                    if (!$list instanceof ListEnumFieldsHandler) {
                        throw new LogicException('ListEnumFields handler service is invalid.');
                    }

                    if (!$get instanceof GetEnumFieldByIdHandler) {
                        throw new LogicException('GetEnumFieldById handler service is invalid.');
                    }

                    if (!$create instanceof CreateEnumFieldHandler) {
                        throw new LogicException('CreateEnumField handler service is invalid.');
                    }

                    if (!$update instanceof UpdateEnumFieldHandler) {
                        throw new LogicException('UpdateEnumField handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteEnumFieldHandler) {
                        throw new LogicException('DeleteEnumField handler service is invalid.');
                    }

                    return new EnumFieldRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
