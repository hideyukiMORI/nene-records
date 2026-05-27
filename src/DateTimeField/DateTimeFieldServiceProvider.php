<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

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

final readonly class DateTimeFieldServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DateTimeFieldRepositoryInterface::class,
                static function (ContainerInterface $container): DateTimeFieldRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoDateTimeFieldRepository($query, $orgId);
                },
            )
            ->set(
                CreateDateTimeFieldUseCaseInterface::class,
                static function (ContainerInterface $container): CreateDateTimeFieldUseCaseInterface {
                    $intFields = $container->get(DateTimeFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$intFields instanceof DateTimeFieldRepositoryInterface) {
                        throw new LogicException('datetime field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new CreateDateTimeFieldUseCase($intFields, $entities, $fieldDefs);
                },
            )
            ->set(
                CreateDateTimeFieldHandler::class,
                static function (ContainerInterface $container): CreateDateTimeFieldHandler {
                    $useCase = $container->get(CreateDateTimeFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateDateTimeFieldUseCaseInterface) {
                        throw new LogicException('CreateDateTimeField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateDateTimeFieldHandler($useCase, $response);
                },
            )
            ->set(
                DeleteDateTimeFieldUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteDateTimeFieldUseCaseInterface {
                    $repository = $container->get(DateTimeFieldRepositoryInterface::class);

                    if (!$repository instanceof DateTimeFieldRepositoryInterface) {
                        throw new LogicException('datetime field repository service is invalid.');
                    }

                    return new DeleteDateTimeFieldUseCase($repository);
                },
            )
            ->set(
                DeleteDateTimeFieldHandler::class,
                static function (ContainerInterface $container): DeleteDateTimeFieldHandler {
                    $useCase = $container->get(DeleteDateTimeFieldUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteDateTimeFieldUseCaseInterface) {
                        throw new LogicException('DeleteDateTimeField use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteDateTimeFieldHandler($useCase, $responseFactory);
                },
            )
            ->set(
                GetDateTimeFieldByIdUseCaseInterface::class,
                static function (ContainerInterface $container): GetDateTimeFieldByIdUseCaseInterface {
                    $repository = $container->get(DateTimeFieldRepositoryInterface::class);

                    if (!$repository instanceof DateTimeFieldRepositoryInterface) {
                        throw new LogicException('datetime field repository service is invalid.');
                    }

                    return new GetDateTimeFieldByIdUseCase($repository);
                },
            )
            ->set(
                GetDateTimeFieldByIdHandler::class,
                static function (ContainerInterface $container): GetDateTimeFieldByIdHandler {
                    $useCase = $container->get(GetDateTimeFieldByIdUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetDateTimeFieldByIdUseCaseInterface) {
                        throw new LogicException('GetDateTimeFieldById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetDateTimeFieldByIdHandler($useCase, $response);
                },
            )
            ->set(
                ListDateTimeFieldsUseCaseInterface::class,
                static function (ContainerInterface $container): ListDateTimeFieldsUseCaseInterface {
                    $repository = $container->get(DateTimeFieldRepositoryInterface::class);

                    if (!$repository instanceof DateTimeFieldRepositoryInterface) {
                        throw new LogicException('datetime field repository service is invalid.');
                    }

                    return new ListDateTimeFieldsUseCase($repository);
                },
            )
            ->set(
                ListDateTimeFieldsHandler::class,
                static function (ContainerInterface $container): ListDateTimeFieldsHandler {
                    $useCase = $container->get(ListDateTimeFieldsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListDateTimeFieldsUseCaseInterface) {
                        throw new LogicException('ListDateTimeFields use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListDateTimeFieldsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateDateTimeFieldUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateDateTimeFieldUseCaseInterface {
                    $intFields = $container->get(DateTimeFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$intFields instanceof DateTimeFieldRepositoryInterface) {
                        throw new LogicException('datetime field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new UpdateDateTimeFieldUseCase($intFields, $entities, $fieldDefs);
                },
            )
            ->set(
                UpdateDateTimeFieldHandler::class,
                static function (ContainerInterface $container): UpdateDateTimeFieldHandler {
                    $useCase = $container->get(UpdateDateTimeFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateDateTimeFieldUseCaseInterface) {
                        throw new LogicException('UpdateDateTimeField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateDateTimeFieldHandler($useCase, $response);
                },
            )
            ->set(
                DateTimeFieldNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): DateTimeFieldNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new DateTimeFieldNotFoundExceptionHandler($problemDetails);
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
                'nene-records.route_registrar.datetime_field',
                static function (ContainerInterface $container): DateTimeFieldRouteRegistrar {
                    $list = $container->get(ListDateTimeFieldsHandler::class);
                    $get = $container->get(GetDateTimeFieldByIdHandler::class);
                    $create = $container->get(CreateDateTimeFieldHandler::class);
                    $update = $container->get(UpdateDateTimeFieldHandler::class);
                    $delete = $container->get(DeleteDateTimeFieldHandler::class);

                    if (!$list instanceof ListDateTimeFieldsHandler) {
                        throw new LogicException('ListDateTimeFields handler service is invalid.');
                    }

                    if (!$get instanceof GetDateTimeFieldByIdHandler) {
                        throw new LogicException('GetDateTimeFieldById handler service is invalid.');
                    }

                    if (!$create instanceof CreateDateTimeFieldHandler) {
                        throw new LogicException('CreateDateTimeField handler service is invalid.');
                    }

                    if (!$update instanceof UpdateDateTimeFieldHandler) {
                        throw new LogicException('UpdateDateTimeField handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteDateTimeFieldHandler) {
                        throw new LogicException('DeleteDateTimeField handler service is invalid.');
                    }

                    return new DateTimeFieldRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
