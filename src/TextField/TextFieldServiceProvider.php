<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class TextFieldServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                TextFieldRepositoryInterface::class,
                static function (ContainerInterface $container): TextFieldRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoTextFieldRepository($query);
                },
            )
            ->set(
                CreateTextFieldUseCaseInterface::class,
                static function (ContainerInterface $container): CreateTextFieldUseCaseInterface {
                    $textFields = $container->get(TextFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new CreateTextFieldUseCase($textFields, $entities, $fieldDefs);
                },
            )
            ->set(
                CreateTextFieldHandler::class,
                static function (ContainerInterface $container): CreateTextFieldHandler {
                    $useCase = $container->get(CreateTextFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateTextFieldUseCaseInterface) {
                        throw new LogicException('CreateTextField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateTextFieldHandler($useCase, $response);
                },
            )
            ->set(
                DeleteTextFieldUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteTextFieldUseCaseInterface {
                    $repository = $container->get(TextFieldRepositoryInterface::class);

                    if (!$repository instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    return new DeleteTextFieldUseCase($repository);
                },
            )
            ->set(
                DeleteTextFieldHandler::class,
                static function (ContainerInterface $container): DeleteTextFieldHandler {
                    $useCase = $container->get(DeleteTextFieldUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteTextFieldUseCaseInterface) {
                        throw new LogicException('DeleteTextField use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteTextFieldHandler($useCase, $responseFactory);
                },
            )
            ->set(
                GetTextFieldByIdUseCaseInterface::class,
                static function (ContainerInterface $container): GetTextFieldByIdUseCaseInterface {
                    $repository = $container->get(TextFieldRepositoryInterface::class);

                    if (!$repository instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    return new GetTextFieldByIdUseCase($repository);
                },
            )
            ->set(
                GetTextFieldByIdHandler::class,
                static function (ContainerInterface $container): GetTextFieldByIdHandler {
                    $useCase = $container->get(GetTextFieldByIdUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetTextFieldByIdUseCaseInterface) {
                        throw new LogicException('GetTextFieldById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetTextFieldByIdHandler($useCase, $response);
                },
            )
            ->set(
                ListTextFieldsUseCaseInterface::class,
                static function (ContainerInterface $container): ListTextFieldsUseCaseInterface {
                    $repository = $container->get(TextFieldRepositoryInterface::class);

                    if (!$repository instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    return new ListTextFieldsUseCase($repository);
                },
            )
            ->set(
                ListTextFieldsHandler::class,
                static function (ContainerInterface $container): ListTextFieldsHandler {
                    $useCase = $container->get(ListTextFieldsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListTextFieldsUseCaseInterface) {
                        throw new LogicException('ListTextFields use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListTextFieldsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateTextFieldUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateTextFieldUseCaseInterface {
                    $textFields = $container->get(TextFieldRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);

                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field definition repository service is invalid.');
                    }

                    return new UpdateTextFieldUseCase($textFields, $entities, $fieldDefs);
                },
            )
            ->set(
                UpdateTextFieldHandler::class,
                static function (ContainerInterface $container): UpdateTextFieldHandler {
                    $useCase = $container->get(UpdateTextFieldUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateTextFieldUseCaseInterface) {
                        throw new LogicException('UpdateTextField use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateTextFieldHandler($useCase, $response);
                },
            )
            ->set(
                TextFieldNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): TextFieldNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new TextFieldNotFoundExceptionHandler($problemDetails);
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
                'nene-records.route_registrar.text_field',
                static function (ContainerInterface $container): TextFieldRouteRegistrar {
                    $list = $container->get(ListTextFieldsHandler::class);
                    $get = $container->get(GetTextFieldByIdHandler::class);
                    $create = $container->get(CreateTextFieldHandler::class);
                    $update = $container->get(UpdateTextFieldHandler::class);
                    $delete = $container->get(DeleteTextFieldHandler::class);

                    if (!$list instanceof ListTextFieldsHandler) {
                        throw new LogicException('ListTextFields handler service is invalid.');
                    }

                    if (!$get instanceof GetTextFieldByIdHandler) {
                        throw new LogicException('GetTextFieldById handler service is invalid.');
                    }

                    if (!$create instanceof CreateTextFieldHandler) {
                        throw new LogicException('CreateTextField handler service is invalid.');
                    }

                    if (!$update instanceof UpdateTextFieldHandler) {
                        throw new LogicException('UpdateTextField handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteTextFieldHandler) {
                        throw new LogicException('DeleteTextField handler service is invalid.');
                    }

                    return new TextFieldRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
