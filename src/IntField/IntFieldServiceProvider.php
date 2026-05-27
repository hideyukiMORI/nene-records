<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

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

final readonly class IntFieldServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                IntFieldRepositoryInterface::class,
                static function (ContainerInterface $container): IntFieldRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoIntFieldRepository($query, $orgId);
                },
            )
            ->set(
                CreateIntFieldUseCaseInterface::class,
                static function (ContainerInterface $container): CreateIntFieldUseCaseInterface {
                    $intFields = $container->get(IntFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$intFields instanceof IntFieldRepositoryInterface) {
                        throw new LogicException('Int field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new CreateIntFieldUseCase($intFields, $entities, $fieldDefs);
                },
            )
            ->set(
                CreateIntFieldHandler::class,
                static function (ContainerInterface $container): CreateIntFieldHandler {
                    $useCase = $container->get(CreateIntFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateIntFieldUseCaseInterface) {
                        throw new LogicException('CreateIntField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateIntFieldHandler($useCase, $response);
                },
            )
            ->set(
                DeleteIntFieldUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteIntFieldUseCaseInterface {
                    $repository = $container->get(IntFieldRepositoryInterface::class);

                    if (!$repository instanceof IntFieldRepositoryInterface) {
                        throw new LogicException('Int field repository service is invalid.');
                    }

                    return new DeleteIntFieldUseCase($repository);
                },
            )
            ->set(
                DeleteIntFieldHandler::class,
                static function (ContainerInterface $container): DeleteIntFieldHandler {
                    $useCase = $container->get(DeleteIntFieldUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteIntFieldUseCaseInterface) {
                        throw new LogicException('DeleteIntField use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteIntFieldHandler($useCase, $responseFactory);
                },
            )
            ->set(
                GetIntFieldByIdUseCaseInterface::class,
                static function (ContainerInterface $container): GetIntFieldByIdUseCaseInterface {
                    $repository = $container->get(IntFieldRepositoryInterface::class);

                    if (!$repository instanceof IntFieldRepositoryInterface) {
                        throw new LogicException('Int field repository service is invalid.');
                    }

                    return new GetIntFieldByIdUseCase($repository);
                },
            )
            ->set(
                GetIntFieldByIdHandler::class,
                static function (ContainerInterface $container): GetIntFieldByIdHandler {
                    $useCase = $container->get(GetIntFieldByIdUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetIntFieldByIdUseCaseInterface) {
                        throw new LogicException('GetIntFieldById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetIntFieldByIdHandler($useCase, $response);
                },
            )
            ->set(
                ListIntFieldsUseCaseInterface::class,
                static function (ContainerInterface $container): ListIntFieldsUseCaseInterface {
                    $repository = $container->get(IntFieldRepositoryInterface::class);

                    if (!$repository instanceof IntFieldRepositoryInterface) {
                        throw new LogicException('Int field repository service is invalid.');
                    }

                    return new ListIntFieldsUseCase($repository);
                },
            )
            ->set(
                ListIntFieldsHandler::class,
                static function (ContainerInterface $container): ListIntFieldsHandler {
                    $useCase = $container->get(ListIntFieldsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListIntFieldsUseCaseInterface) {
                        throw new LogicException('ListIntFields use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListIntFieldsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateIntFieldUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateIntFieldUseCaseInterface {
                    $intFields = $container->get(IntFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$intFields instanceof IntFieldRepositoryInterface) {
                        throw new LogicException('Int field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new UpdateIntFieldUseCase($intFields, $entities, $fieldDefs);
                },
            )
            ->set(
                UpdateIntFieldHandler::class,
                static function (ContainerInterface $container): UpdateIntFieldHandler {
                    $useCase = $container->get(UpdateIntFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateIntFieldUseCaseInterface) {
                        throw new LogicException('UpdateIntField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateIntFieldHandler($useCase, $response);
                },
            )
            ->set(
                IntFieldNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): IntFieldNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new IntFieldNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                FieldKeyNotRegisteredExceptionHandler::class,
                static function (ContainerInterface $container): FieldKeyNotRegisteredExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new FieldKeyNotRegisteredExceptionHandler($problemDetails);
                },
            )
            ->set(
                FieldTypeMismatchExceptionHandler::class,
                static function (ContainerInterface $container): FieldTypeMismatchExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new FieldTypeMismatchExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.int_field',
                static function (ContainerInterface $container): IntFieldRouteRegistrar {
                    $list = $container->get(ListIntFieldsHandler::class);
                    $get = $container->get(GetIntFieldByIdHandler::class);
                    $create = $container->get(CreateIntFieldHandler::class);
                    $update = $container->get(UpdateIntFieldHandler::class);
                    $delete = $container->get(DeleteIntFieldHandler::class);

                    if (!$list instanceof ListIntFieldsHandler) {
                        throw new LogicException('ListIntFields handler service is invalid.');
                    }

                    if (!$get instanceof GetIntFieldByIdHandler) {
                        throw new LogicException('GetIntFieldById handler service is invalid.');
                    }

                    if (!$create instanceof CreateIntFieldHandler) {
                        throw new LogicException('CreateIntField handler service is invalid.');
                    }

                    if (!$update instanceof UpdateIntFieldHandler) {
                        throw new LogicException('UpdateIntField handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteIntFieldHandler) {
                        throw new LogicException('DeleteIntField handler service is invalid.');
                    }

                    return new IntFieldRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
