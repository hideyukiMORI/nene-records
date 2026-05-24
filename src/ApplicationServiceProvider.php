<?php

declare(strict_types=1);

namespace NeNeRecords;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityServiceProvider;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\EntityType\EntityTypeServiceProvider;
use NeNeRecords\EntityType\EntityTypeSlugConflictExceptionHandler;
use NeNeRecords\FieldDef\FieldDefConflictExceptionHandler;
use NeNeRecords\FieldDef\FieldDefNotFoundExceptionHandler;
use NeNeRecords\FieldDef\FieldDefServiceProvider;
use NeNeRecords\TextField\FieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\TextField\FieldTypeMismatchExceptionHandler;
use NeNeRecords\TextField\TextFieldNotFoundExceptionHandler;
use NeNeRecords\TextField\TextFieldServiceProvider;
use Psr\Container\ContainerInterface;

final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const ROUTE_REGISTRARS = 'nene-records.route_registrars';

    public const EXCEPTION_HANDLERS = 'nene-records.exception_handlers';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->addProvider(new EntityTypeServiceProvider())
            ->addProvider(new FieldDefServiceProvider())
            ->addProvider(new EntityServiceProvider())
            ->addProvider(new TextFieldServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $entityType = $container->get('nene-records.route_registrar.entity_type');
                    $fieldDef = $container->get('nene-records.route_registrar.field_def');
                    $entity = $container->get('nene-records.route_registrar.entity');
                    $textField = $container->get('nene-records.route_registrar.text_field');

                    if (
                        !is_callable($entityType)
                        || !is_callable($fieldDef)
                        || !is_callable($entity)
                        || !is_callable($textField)
                    ) {
                        throw new LogicException('Route registrar service is invalid.');
                    }

                    return [$entityType, $fieldDef, $entity, $textField];
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $entityTypeNotFound = $container->get(EntityTypeNotFoundExceptionHandler::class);
                    $entityTypeSlugConflict = $container->get(EntityTypeSlugConflictExceptionHandler::class);
                    $fieldDefNotFound = $container->get(FieldDefNotFoundExceptionHandler::class);
                    $fieldDefConflict = $container->get(FieldDefConflictExceptionHandler::class);
                    $entityNotFound = $container->get(EntityNotFoundExceptionHandler::class);
                    $textFieldNotFound = $container->get(TextFieldNotFoundExceptionHandler::class);
                    $fieldKeyNotRegistered = $container->get(FieldKeyNotRegisteredExceptionHandler::class);
                    $fieldTypeMismatch = $container->get(FieldTypeMismatchExceptionHandler::class);

                    foreach ([
                        $entityTypeNotFound,
                        $entityTypeSlugConflict,
                        $fieldDefNotFound,
                        $fieldDefConflict,
                        $entityNotFound,
                        $textFieldNotFound,
                        $fieldKeyNotRegistered,
                        $fieldTypeMismatch,
                    ] as $handler) {
                        if (!$handler instanceof DomainExceptionHandlerInterface) {
                            throw new LogicException('Exception handler service is invalid.');
                        }
                    }

                    return [
                        $entityTypeNotFound,
                        $entityTypeSlugConflict,
                        $fieldDefNotFound,
                        $fieldDefConflict,
                        $entityNotFound,
                        $textFieldNotFound,
                        $fieldKeyNotRegistered,
                        $fieldTypeMismatch,
                    ];
                },
            );
    }
}
