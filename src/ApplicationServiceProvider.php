<?php

declare(strict_types=1);

namespace NeNeRecords;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use NeNeRecords\Analytics\AnalyticsServiceProvider;
use NeNeRecords\Auth\InvalidCredentialsExceptionHandler;
use NeNeRecords\BoolField\BoolFieldNotFoundExceptionHandler;
use NeNeRecords\BoolField\BoolFieldServiceProvider;
use NeNeRecords\BoolField\FieldKeyNotRegisteredExceptionHandler as BoolFieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\BoolField\FieldTypeMismatchExceptionHandler as BoolFieldTypeMismatchExceptionHandler;
use NeNeRecords\DateTimeField\DateTimeFieldNotFoundExceptionHandler;
use NeNeRecords\DateTimeField\DateTimeFieldServiceProvider;
use NeNeRecords\DateTimeField\FieldKeyNotRegisteredExceptionHandler as DateTimeFieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\DateTimeField\FieldTypeMismatchExceptionHandler as DateTimeFieldTypeMismatchExceptionHandler;
use NeNeRecords\Entity\DuplicateEntitySlugExceptionHandler;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityServiceProvider;
use NeNeRecords\EntityArchive\EntityArchiveServiceProvider;
use NeNeRecords\EntityRelation\EntityRelationServiceProvider;
use NeNeRecords\EntityRelation\FieldKeyNotRegisteredExceptionHandler as EntityRelationFieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\EntityRelation\FieldTypeMismatchExceptionHandler as EntityRelationFieldTypeMismatchExceptionHandler;
use NeNeRecords\EntityRelation\RelationAlreadyAttachedExceptionHandler;
use NeNeRecords\EntityRelation\RelationNotAttachedExceptionHandler;
use NeNeRecords\EntityRelation\RelationTargetTypeMismatchExceptionHandler;
use NeNeRecords\EntityTag\EntityTagAlreadyAttachedExceptionHandler;
use NeNeRecords\EntityTag\EntityTagNotAttachedExceptionHandler;
use NeNeRecords\EntityTag\EntityTagServiceProvider;
use NeNeRecords\EntityType\EntityTypeHasEntitiesExceptionHandler;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\EntityType\EntityTypeServiceProvider;
use NeNeRecords\EntityType\EntityTypeSlugConflictExceptionHandler;
use NeNeRecords\EnumField\EnumFieldNotFoundExceptionHandler;
use NeNeRecords\EnumField\EnumFieldServiceProvider;
use NeNeRecords\EnumField\FieldKeyNotRegisteredExceptionHandler as EnumFieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\EnumField\FieldTypeMismatchExceptionHandler as EnumFieldTypeMismatchExceptionHandler;
use NeNeRecords\FieldDef\FieldDefConflictExceptionHandler;
use NeNeRecords\FieldDef\FieldDefNotFoundExceptionHandler;
use NeNeRecords\FieldDef\FieldDefServiceProvider;
use NeNeRecords\IntField\FieldKeyNotRegisteredExceptionHandler as IntFieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\IntField\FieldTypeMismatchExceptionHandler as IntFieldTypeMismatchExceptionHandler;
use NeNeRecords\IntField\IntFieldNotFoundExceptionHandler;
use NeNeRecords\IntField\IntFieldServiceProvider;
use NeNeRecords\Media\MediaInvalidTypeExceptionHandler;
use NeNeRecords\Media\MediaServiceProvider;
use NeNeRecords\Media\MediaTooLargeExceptionHandler;
use NeNeRecords\NavigationItem\NavigationItemNotFoundExceptionHandler;
use NeNeRecords\NavigationItem\NavigationItemServiceProvider;
use NeNeRecords\PreviewToken\PreviewTokenNotFoundExceptionHandler;
use NeNeRecords\PreviewToken\PreviewTokenServiceProvider;
use NeNeRecords\PublicRecord\PublicEntityTypeNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordServiceProvider;
use NeNeRecords\Setting\SettingKeyNotFoundExceptionHandler;
use NeNeRecords\Setting\SettingServiceProvider;
use NeNeRecords\Setting\SettingValueInvalidExceptionHandler;
use NeNeRecords\Tag\TagNotFoundExceptionHandler;
use NeNeRecords\Tag\TagServiceProvider;
use NeNeRecords\Tag\TagSlugConflictExceptionHandler;
use NeNeRecords\TextField\FieldKeyNotRegisteredExceptionHandler as TextFieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\TextField\FieldTypeMismatchExceptionHandler as TextFieldTypeMismatchExceptionHandler;
use NeNeRecords\TextField\TextFieldNotFoundExceptionHandler;
use NeNeRecords\TextField\TextFieldServiceProvider;
use NeNeRecords\Webhook\WebhookNotFoundExceptionHandler;
use NeNeRecords\Webhook\WebhookServiceProvider;
use Psr\Container\ContainerInterface;

final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const ROUTE_REGISTRARS = 'nene-records.route_registrars';

    public const EXCEPTION_HANDLERS = 'nene-records.exception_handlers';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->addProvider(new EntityArchiveServiceProvider())
            ->addProvider(new EntityTypeServiceProvider())
            ->addProvider(new FieldDefServiceProvider())
            ->addProvider(new EntityServiceProvider())
            ->addProvider(new TextFieldServiceProvider())
            ->addProvider(new IntFieldServiceProvider())
            ->addProvider(new EnumFieldServiceProvider())
            ->addProvider(new BoolFieldServiceProvider())
            ->addProvider(new DateTimeFieldServiceProvider())
            ->addProvider(new TagServiceProvider())
            ->addProvider(new EntityTagServiceProvider())
            ->addProvider(new EntityRelationServiceProvider())
            ->addProvider(new AnalyticsServiceProvider())
            ->addProvider(new MediaServiceProvider())
            ->addProvider(new PublicRecordServiceProvider())
            ->addProvider(new SettingServiceProvider())
            ->addProvider(new NavigationItemServiceProvider())
            ->addProvider(new WebhookServiceProvider())
            ->addProvider(new PreviewTokenServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $entityType = $container->get('nene-records.route_registrar.entity_type');
                    $entityArchive = $container->get('nene-records.route_registrar.entity_archive');
                    $fieldDef = $container->get('nene-records.route_registrar.field_def');
                    $entity = $container->get('nene-records.route_registrar.entity');
                    $textField = $container->get('nene-records.route_registrar.text_field');
                    $intField = $container->get('nene-records.route_registrar.int_field');
                    $enumField = $container->get('nene-records.route_registrar.enum_field');
                    $boolField = $container->get('nene-records.route_registrar.bool_field');
                    $datetimeField = $container->get('nene-records.route_registrar.datetime_field');
                    $tag = $container->get('nene-records.route_registrar.tag');
                    $entityTag = $container->get('nene-records.route_registrar.entity_tag');
                    $entityRelation = $container->get('nene-records.route_registrar.entity_relation');
                    $media = $container->get('nene-records.route_registrar.media');
                    $analytics = $container->get('nene-records.route_registrar.analytics');
                    $publicRecord = $container->get('nene-records.route_registrar.public_record');
                    $setting = $container->get('nene-records.route_registrar.setting');
                    $navigationItem = $container->get('nene-records.route_registrar.navigation_item');
                    $webhook = $container->get('nene-records.route_registrar.webhook');
                    $previewToken = $container->get('nene-records.route_registrar.preview_token');
                    $auth = $container->get('nene-records.route_registrar.auth');

                    if (
                        !is_callable($entityType)
                        || !is_callable($entityArchive)
                        || !is_callable($fieldDef)
                        || !is_callable($entity)
                        || !is_callable($textField)
                        || !is_callable($intField)
                        || !is_callable($enumField)
                        || !is_callable($boolField)
                        || !is_callable($datetimeField)
                        || !is_callable($tag)
                        || !is_callable($entityTag)
                        || !is_callable($entityRelation)
                        || !is_callable($media)
                        || !is_callable($analytics)
                        || !is_callable($publicRecord)
                        || !is_callable($setting)
                        || !is_callable($navigationItem)
                        || !is_callable($webhook)
                        || !is_callable($previewToken)
                        || !is_callable($auth)
                    ) {
                        throw new LogicException('Route registrar service is invalid.');
                    }

                    return [
                        $auth,
                        $entityType,
                        $entityArchive,
                        $fieldDef,
                        $entity,
                        $textField,
                        $intField,
                        $enumField,
                        $boolField,
                        $datetimeField,
                        $tag,
                        $entityTag,
                        $entityRelation,
                        $media,
                        $analytics,
                        $publicRecord,
                        $setting,
                        $navigationItem,
                        $webhook,
                        $previewToken,
                    ];
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $entityTypeNotFound = $container->get(EntityTypeNotFoundExceptionHandler::class);
                    $entityTypeHasEntities = $container->get(EntityTypeHasEntitiesExceptionHandler::class);
                    $entityTypeSlugConflict = $container->get(EntityTypeSlugConflictExceptionHandler::class);
                    $fieldDefNotFound = $container->get(FieldDefNotFoundExceptionHandler::class);
                    $fieldDefConflict = $container->get(FieldDefConflictExceptionHandler::class);
                    $entityNotFound = $container->get(EntityNotFoundExceptionHandler::class);
                    $duplicateEntitySlug = $container->get(DuplicateEntitySlugExceptionHandler::class);
                    $textFieldNotFound = $container->get(TextFieldNotFoundExceptionHandler::class);
                    $textFieldKeyNotRegistered = $container->get(TextFieldKeyNotRegisteredExceptionHandler::class);
                    $textFieldTypeMismatch = $container->get(TextFieldTypeMismatchExceptionHandler::class);
                    $intFieldNotFound = $container->get(IntFieldNotFoundExceptionHandler::class);
                    $intFieldKeyNotRegistered = $container->get(IntFieldKeyNotRegisteredExceptionHandler::class);
                    $intFieldTypeMismatch = $container->get(IntFieldTypeMismatchExceptionHandler::class);
                    $enumFieldNotFound = $container->get(EnumFieldNotFoundExceptionHandler::class);
                    $enumFieldKeyNotRegistered = $container->get(EnumFieldKeyNotRegisteredExceptionHandler::class);
                    $enumFieldTypeMismatch = $container->get(EnumFieldTypeMismatchExceptionHandler::class);
                    $boolFieldNotFound = $container->get(BoolFieldNotFoundExceptionHandler::class);
                    $boolFieldKeyNotRegistered = $container->get(BoolFieldKeyNotRegisteredExceptionHandler::class);
                    $boolFieldTypeMismatch = $container->get(BoolFieldTypeMismatchExceptionHandler::class);
                    $datetimeFieldNotFound = $container->get(DateTimeFieldNotFoundExceptionHandler::class);
                    $datetimeFieldKeyNotRegistered = $container->get(DateTimeFieldKeyNotRegisteredExceptionHandler::class);
                    $datetimeFieldTypeMismatch = $container->get(DateTimeFieldTypeMismatchExceptionHandler::class);
                    $tagNotFound = $container->get(TagNotFoundExceptionHandler::class);
                    $tagSlugConflict = $container->get(TagSlugConflictExceptionHandler::class);
                    $entityTagAlreadyAttached = $container->get(EntityTagAlreadyAttachedExceptionHandler::class);
                    $entityTagNotAttached = $container->get(EntityTagNotAttachedExceptionHandler::class);
                    $entityRelationFieldKeyNotRegistered = $container->get(EntityRelationFieldKeyNotRegisteredExceptionHandler::class);
                    $entityRelationFieldTypeMismatch = $container->get(EntityRelationFieldTypeMismatchExceptionHandler::class);
                    $relationTargetTypeMismatch = $container->get(RelationTargetTypeMismatchExceptionHandler::class);
                    $relationAlreadyAttached = $container->get(RelationAlreadyAttachedExceptionHandler::class);
                    $relationNotAttached = $container->get(RelationNotAttachedExceptionHandler::class);
                    $publicEntityTypeNotFound = $container->get(PublicEntityTypeNotFoundExceptionHandler::class);
                    $publicRecordNotFound = $container->get(PublicRecordNotFoundExceptionHandler::class);
                    $settingKeyNotFound = $container->get(SettingKeyNotFoundExceptionHandler::class);
                    $settingValueInvalid = $container->get(SettingValueInvalidExceptionHandler::class);
                    $invalidCredentials = $container->get(InvalidCredentialsExceptionHandler::class);
                    $mediaInvalidType = $container->get(MediaInvalidTypeExceptionHandler::class);
                    $mediaTooLarge = $container->get(MediaTooLargeExceptionHandler::class);
                    $navigationItemNotFound = $container->get(NavigationItemNotFoundExceptionHandler::class);
                    $webhookNotFound = $container->get(WebhookNotFoundExceptionHandler::class);
                    $previewTokenNotFound = $container->get(PreviewTokenNotFoundExceptionHandler::class);

                    foreach ([
                        $entityTypeNotFound,
                        $entityTypeHasEntities,
                        $entityTypeSlugConflict,
                        $fieldDefNotFound,
                        $fieldDefConflict,
                        $entityNotFound,
                        $duplicateEntitySlug,
                        $textFieldNotFound,
                        $textFieldKeyNotRegistered,
                        $textFieldTypeMismatch,
                        $intFieldNotFound,
                        $intFieldKeyNotRegistered,
                        $intFieldTypeMismatch,
                        $enumFieldNotFound,
                        $enumFieldKeyNotRegistered,
                        $enumFieldTypeMismatch,
                        $boolFieldNotFound,
                        $boolFieldKeyNotRegistered,
                        $boolFieldTypeMismatch,
                        $datetimeFieldNotFound,
                        $datetimeFieldKeyNotRegistered,
                        $datetimeFieldTypeMismatch,
                        $tagNotFound,
                        $tagSlugConflict,
                        $entityTagAlreadyAttached,
                        $entityTagNotAttached,
                        $entityRelationFieldKeyNotRegistered,
                        $entityRelationFieldTypeMismatch,
                        $relationTargetTypeMismatch,
                        $relationAlreadyAttached,
                        $relationNotAttached,
                        $publicEntityTypeNotFound,
                        $publicRecordNotFound,
                        $settingKeyNotFound,
                        $settingValueInvalid,
                        $invalidCredentials,
                        $mediaInvalidType,
                        $mediaTooLarge,
                        $navigationItemNotFound,
                        $webhookNotFound,
                        $previewTokenNotFound,
                    ] as $handler) {
                        if (!$handler instanceof DomainExceptionHandlerInterface) {
                            throw new LogicException('Exception handler service is invalid.');
                        }
                    }

                    return [
                        $entityTypeNotFound,
                        $entityTypeHasEntities,
                        $entityTypeSlugConflict,
                        $fieldDefNotFound,
                        $fieldDefConflict,
                        $entityNotFound,
                        $duplicateEntitySlug,
                        $textFieldNotFound,
                        $textFieldKeyNotRegistered,
                        $textFieldTypeMismatch,
                        $intFieldNotFound,
                        $intFieldKeyNotRegistered,
                        $intFieldTypeMismatch,
                        $enumFieldNotFound,
                        $enumFieldKeyNotRegistered,
                        $enumFieldTypeMismatch,
                        $boolFieldNotFound,
                        $boolFieldKeyNotRegistered,
                        $boolFieldTypeMismatch,
                        $datetimeFieldNotFound,
                        $datetimeFieldKeyNotRegistered,
                        $datetimeFieldTypeMismatch,
                        $tagNotFound,
                        $tagSlugConflict,
                        $entityTagAlreadyAttached,
                        $entityTagNotAttached,
                        $entityRelationFieldKeyNotRegistered,
                        $entityRelationFieldTypeMismatch,
                        $relationTargetTypeMismatch,
                        $relationAlreadyAttached,
                        $relationNotAttached,
                        $publicEntityTypeNotFound,
                        $publicRecordNotFound,
                        $settingKeyNotFound,
                        $settingValueInvalid,
                        $invalidCredentials,
                        $mediaInvalidType,
                        $mediaTooLarge,
                        $navigationItemNotFound,
                        $webhookNotFound,
                        $previewTokenNotFound,
                    ];
                },
            );
    }
}
