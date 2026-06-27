<?php

declare(strict_types=1);

namespace NeNeRecords;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Account\AccountRouteRegistrar;
use NeNeRecords\Account\AccountServiceProvider;
use NeNeRecords\Analytics\AnalyticsRouteRegistrar;
use NeNeRecords\Analytics\AnalyticsServiceProvider;
use NeNeRecords\Auth\AuthRouteRegistrar;
use NeNeRecords\Auth\InvalidCredentialsExceptionHandler;
use NeNeRecords\BlocksField\BlocksFieldNotFoundExceptionHandler;
use NeNeRecords\BlocksField\BlocksFieldRouteRegistrar;
use NeNeRecords\BlocksField\BlocksFieldServiceProvider;
use NeNeRecords\BoolField\BoolFieldNotFoundExceptionHandler;
use NeNeRecords\BoolField\BoolFieldRouteRegistrar;
use NeNeRecords\BoolField\BoolFieldServiceProvider;
use NeNeRecords\Comment\CommentNotFoundExceptionHandler;
use NeNeRecords\Comment\CommentRouteRegistrar;
use NeNeRecords\Comment\CommentServiceProvider;
use NeNeRecords\Dashboard\DashboardRouteRegistrar;
use NeNeRecords\Dashboard\DashboardServiceProvider;
use NeNeRecords\DataMigration\DataMigrationRouteRegistrar;
use NeNeRecords\DataMigration\DataMigrationServiceProvider;
use NeNeRecords\DateTimeField\DateTimeFieldNotFoundExceptionHandler;
use NeNeRecords\DateTimeField\DateTimeFieldRouteRegistrar;
use NeNeRecords\DateTimeField\DateTimeFieldServiceProvider;
use NeNeRecords\Entitlement\EntitlementServiceProvider;
use NeNeRecords\Entitlement\FeatureNotEntitledExceptionHandler;
use NeNeRecords\Entity\DuplicateEntityPermalinkExceptionHandler;
use NeNeRecords\Entity\DuplicateEntitySlugExceptionHandler;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityRouteRegistrar;
use NeNeRecords\Entity\EntityServiceProvider;
use NeNeRecords\EntityArchive\EntityArchiveRouteRegistrar;
use NeNeRecords\EntityArchive\EntityArchiveServiceProvider;
use NeNeRecords\EntityRelation\EntityRelationRouteRegistrar;
use NeNeRecords\EntityRelation\EntityRelationServiceProvider;
use NeNeRecords\EntityRelation\RelationAlreadyAttachedExceptionHandler;
use NeNeRecords\EntityRelation\RelationNotAttachedExceptionHandler;
use NeNeRecords\EntityRelation\RelationTargetTypeMismatchExceptionHandler;
use NeNeRecords\EntityTag\EntityTagAlreadyAttachedExceptionHandler;
use NeNeRecords\EntityTag\EntityTagNotAttachedExceptionHandler;
use NeNeRecords\EntityTag\EntityTagRouteRegistrar;
use NeNeRecords\EntityTag\EntityTagServiceProvider;
use NeNeRecords\EntityType\EntityTypeHasEntitiesExceptionHandler;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\EntityType\EntityTypeRouteRegistrar;
use NeNeRecords\EntityType\EntityTypeServiceProvider;
use NeNeRecords\EntityType\EntityTypeSlugConflictExceptionHandler;
use NeNeRecords\EnumField\EnumFieldNotFoundExceptionHandler;
use NeNeRecords\EnumField\EnumFieldRouteRegistrar;
use NeNeRecords\EnumField\EnumFieldServiceProvider;
use NeNeRecords\Extension\ModuleRegistry;
use NeNeRecords\FieldDef\FieldDefConflictExceptionHandler;
use NeNeRecords\FieldDef\FieldDefNotFoundExceptionHandler;
use NeNeRecords\FieldDef\FieldDefRouteRegistrar;
use NeNeRecords\FieldDef\FieldDefServiceProvider;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredExceptionHandler;
use NeNeRecords\FieldDef\FieldTypeMismatchExceptionHandler;
use NeNeRecords\Http\SingleOriginServiceProvider;
use NeNeRecords\IntField\IntFieldNotFoundExceptionHandler;
use NeNeRecords\IntField\IntFieldRouteRegistrar;
use NeNeRecords\IntField\IntFieldServiceProvider;
use NeNeRecords\Media\MediaInUseExceptionHandler;
use NeNeRecords\Media\MediaInvalidTypeExceptionHandler;
use NeNeRecords\Media\MediaNotFoundExceptionHandler;
use NeNeRecords\Media\MediaRouteRegistrar;
use NeNeRecords\Media\MediaServiceProvider;
use NeNeRecords\Media\MediaTooLargeExceptionHandler;
use NeNeRecords\Menu\MenuNotFoundExceptionHandler;
use NeNeRecords\Menu\MenuRouteRegistrar;
use NeNeRecords\Menu\MenuServiceProvider;
use NeNeRecords\NavigationItem\NavigationItemNotFoundExceptionHandler;
use NeNeRecords\NavigationItem\NavigationItemRouteRegistrar;
use NeNeRecords\NavigationItem\NavigationItemServiceProvider;
use NeNeRecords\Notification\NotificationChannelNotFoundExceptionHandler;
use NeNeRecords\Notification\NotificationRouteRegistrar;
use NeNeRecords\Notification\NotificationServiceProvider;
use NeNeRecords\Organization\OrganizationNotFoundExceptionHandler;
use NeNeRecords\Organization\OrganizationRouteRegistrar;
use NeNeRecords\Organization\OrganizationServiceProvider;
use NeNeRecords\Organization\OrganizationSlugConflictExceptionHandler;
use NeNeRecords\Organization\TlsCheckRouteRegistrar;
use NeNeRecords\OrgExport\OrgExportRouteRegistrar;
use NeNeRecords\OrgExport\OrgExportServiceProvider;
use NeNeRecords\PreviewToken\PreviewTokenNotFoundExceptionHandler;
use NeNeRecords\PreviewToken\PreviewTokenRouteRegistrar;
use NeNeRecords\PreviewToken\PreviewTokenServiceProvider;
use NeNeRecords\PublicRecord\PublicEntityTypeNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordNotFoundExceptionHandler;
use NeNeRecords\PublicRecord\PublicRecordRouteRegistrar;
use NeNeRecords\PublicRecord\PublicRecordServiceProvider;
use NeNeRecords\Setting\SettingKeyNotFoundExceptionHandler;
use NeNeRecords\Setting\SettingRouteRegistrar;
use NeNeRecords\Setting\SettingServiceProvider;
use NeNeRecords\Setting\SettingValueInvalidExceptionHandler;
use NeNeRecords\Signup\PublicSignupRouteRegistrar;
use NeNeRecords\Signup\SignupServiceProvider;
use NeNeRecords\SystemConfig\SystemConfigRouteRegistrar;
use NeNeRecords\SystemConfig\SystemConfigServiceProvider;
use NeNeRecords\Tag\TagNotFoundExceptionHandler;
use NeNeRecords\Tag\TagRouteRegistrar;
use NeNeRecords\Tag\TagServiceProvider;
use NeNeRecords\Tag\TagSlugConflictExceptionHandler;
use NeNeRecords\TextField\TextFieldNotFoundExceptionHandler;
use NeNeRecords\TextField\TextFieldRouteRegistrar;
use NeNeRecords\TextField\TextFieldServiceProvider;
use NeNeRecords\Theme\ThemeNotFoundExceptionHandler;
use NeNeRecords\Theme\ThemeRouteRegistrar;
use NeNeRecords\Theme\ThemeServiceProvider;
use NeNeRecords\UrlRedirect\UrlRedirectRouteRegistrar;
use NeNeRecords\UrlRedirect\UrlRedirectServiceProvider;
use NeNeRecords\User\CannotDeleteSelfExceptionHandler;
use NeNeRecords\User\EmailVerificationTokenExceptionHandler;
use NeNeRecords\User\InvalidCurrentPasswordExceptionHandler;
use NeNeRecords\User\InvalidUserRoleExceptionHandler;
use NeNeRecords\User\UserEmailConflictExceptionHandler;
use NeNeRecords\User\UserNotFoundExceptionHandler;
use NeNeRecords\User\UserRouteRegistrar;
use NeNeRecords\User\UserServiceProvider;
use NeNeRecords\UserInvite\InvalidInviteTokenExceptionHandler;
use NeNeRecords\UserInvite\InvalidPasswordResetTokenExceptionHandler;
use NeNeRecords\UserInvite\UserInviteRouteRegistrar;
use NeNeRecords\UserInvite\UserInviteServiceProvider;
use NeNeRecords\Webhook\WebhookNotFoundExceptionHandler;
use NeNeRecords\Webhook\WebhookRouteRegistrar;
use NeNeRecords\Webhook\WebhookServiceProvider;
use NeNeRecords\Widget\WidgetNotFoundExceptionHandler;
use NeNeRecords\Widget\WidgetRouteRegistrar;
use NeNeRecords\Widget\WidgetServiceProvider;
use NeNeRecords\WxrImport\WxrImportRouteRegistrar;
use NeNeRecords\WxrImport\WxrImportServiceProvider;
use Psr\Container\ContainerInterface;

final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const ROUTE_REGISTRARS = 'nene-records.route_registrars';

    public const EXCEPTION_HANDLERS = 'nene-records.exception_handlers';

    /** Container key for the shared RequestScopedHolder<int> that carries org_id. */
    public const ORG_ID_HOLDER = 'nene-records.org_id_holder';

    public function register(ContainerBuilder $builder): void
    {
        // Register the shared org_id holder so all repos and OrgResolverMiddleware share the same instance.
        $builder->set(
            self::ORG_ID_HOLDER,
            static function (): RequestScopedHolder {
                /** @var RequestScopedHolder<int> */
                return new RequestScopedHolder();
            },
        );

        $builder
            ->addProvider(new AccountServiceProvider())
            ->addProvider(new EntityArchiveServiceProvider())
            ->addProvider(new EntityTypeServiceProvider())
            ->addProvider(new FieldDefServiceProvider())
            ->addProvider(new EntityServiceProvider())
            ->addProvider(new TextFieldServiceProvider())
            ->addProvider(new BlocksFieldServiceProvider())
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
            ->addProvider(new MenuServiceProvider())
            ->addProvider(new WidgetServiceProvider())
            ->addProvider(new WebhookServiceProvider())
            ->addProvider(new PreviewTokenServiceProvider())
            ->addProvider(new DashboardServiceProvider())
            ->addProvider(new UserServiceProvider())
            ->addProvider(new UserInviteServiceProvider())
            ->addProvider(new NotificationServiceProvider())
            ->addProvider(new CommentServiceProvider())
            ->addProvider(new EntitlementServiceProvider())
            ->addProvider(new OrganizationServiceProvider())
            ->addProvider(new SignupServiceProvider())
            ->addProvider(new SystemConfigServiceProvider())
            ->addProvider(new DataMigrationServiceProvider())
            ->addProvider(new OrgExportServiceProvider())
            ->addProvider(new ThemeServiceProvider())
            ->addProvider(new WxrImportServiceProvider())
            ->addProvider(new UrlRedirectServiceProvider())
            ->addProvider(new SingleOriginServiceProvider());

        // Optional/private modules (ADR 0005): compose on top of core if present.
        // Absent (fresh git clone) → nothing added; core stays plain OSS.
        foreach ((new ModuleRegistry())->modules() as $module) {
            $builder->addProvider($module);
        }

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $entityType = $container->get('nene-records.route_registrar.entity_type');
                    $entityArchive = $container->get('nene-records.route_registrar.entity_archive');
                    $fieldDef = $container->get('nene-records.route_registrar.field_def');
                    $entity = $container->get('nene-records.route_registrar.entity');
                    $textField = $container->get('nene-records.route_registrar.text_field');
                    $blocksField = $container->get('nene-records.route_registrar.blocks_field');
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
                    $menu = $container->get('nene-records.route_registrar.menu');
                    $widget = $container->get('nene-records.route_registrar.widget');
                    $webhook = $container->get('nene-records.route_registrar.webhook');
                    $previewToken = $container->get('nene-records.route_registrar.preview_token');
                    $dashboard = $container->get('nene-records.route_registrar.dashboard');
                    $account = $container->get('nene-records.route_registrar.account');
                    $user = $container->get('nene-records.route_registrar.user');
                    $userInvite = $container->get('nene-records.route_registrar.user_invite');
                    $auth = $container->get('nene-records.route_registrar.auth');
                    $notification = $container->get(NotificationRouteRegistrar::class);
                    $comment = $container->get(CommentRouteRegistrar::class);
                    $organization = $container->get(OrganizationRouteRegistrar::class);
                    $tlsCheck = $container->get(TlsCheckRouteRegistrar::class);
                    $signup = $container->get(PublicSignupRouteRegistrar::class);
                    $systemConfig = $container->get(SystemConfigRouteRegistrar::class);
                    $dataMigration = $container->get(DataMigrationRouteRegistrar::class);
                    $orgExport     = $container->get(OrgExportRouteRegistrar::class);
                    $theme = $container->get('nene-records.route_registrar.theme');
                    $wxrImport = $container->get('nene-records.route_registrar.wxr_import');
                    $urlRedirect = $container->get('nene-records.route_registrar.url_redirect');

                    if (
                        !$entityType instanceof EntityTypeRouteRegistrar
                        || !$entityArchive instanceof EntityArchiveRouteRegistrar
                        || !$fieldDef instanceof FieldDefRouteRegistrar
                        || !$entity instanceof EntityRouteRegistrar
                        || !$textField instanceof TextFieldRouteRegistrar
                        || !$blocksField instanceof BlocksFieldRouteRegistrar
                        || !$intField instanceof IntFieldRouteRegistrar
                        || !$enumField instanceof EnumFieldRouteRegistrar
                        || !$boolField instanceof BoolFieldRouteRegistrar
                        || !$datetimeField instanceof DateTimeFieldRouteRegistrar
                        || !$tag instanceof TagRouteRegistrar
                        || !$entityTag instanceof EntityTagRouteRegistrar
                        || !$entityRelation instanceof EntityRelationRouteRegistrar
                        || !$media instanceof MediaRouteRegistrar
                        || !$analytics instanceof AnalyticsRouteRegistrar
                        || !$publicRecord instanceof PublicRecordRouteRegistrar
                        || !$setting instanceof SettingRouteRegistrar
                        || !$navigationItem instanceof NavigationItemRouteRegistrar
                        || !$menu instanceof MenuRouteRegistrar
                        || !$widget instanceof WidgetRouteRegistrar
                        || !$webhook instanceof WebhookRouteRegistrar
                        || !$previewToken instanceof PreviewTokenRouteRegistrar
                        || !$dashboard instanceof DashboardRouteRegistrar
                        || !$account instanceof AccountRouteRegistrar
                        || !$user instanceof UserRouteRegistrar
                        || !$userInvite instanceof UserInviteRouteRegistrar
                        || !$auth instanceof AuthRouteRegistrar
                        || !$notification instanceof NotificationRouteRegistrar
                        || !$comment instanceof CommentRouteRegistrar
                        || !$organization instanceof OrganizationRouteRegistrar
                        || !$tlsCheck instanceof TlsCheckRouteRegistrar
                        || !$signup instanceof PublicSignupRouteRegistrar
                        || !$systemConfig instanceof SystemConfigRouteRegistrar
                        || !$dataMigration instanceof DataMigrationRouteRegistrar
                        || !$orgExport instanceof OrgExportRouteRegistrar
                        || !$theme instanceof ThemeRouteRegistrar
                        || !$wxrImport instanceof WxrImportRouteRegistrar
                        || !$urlRedirect instanceof UrlRedirectRouteRegistrar
                    ) {
                        throw new LogicException('Route registrar service is invalid.');
                    }

                    $registrars = [
                        $auth,
                        $entityType,
                        $entityArchive,
                        $fieldDef,
                        $entity,
                        $textField,
                        $blocksField,
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
                        $menu,
                        $widget,
                        $webhook,
                        $previewToken,
                        $dashboard,
                        $account,
                        $user,
                        $userInvite,
                        $notification,
                        $comment,
                        $organization,
                        $tlsCheck,
                        $signup,
                        $systemConfig,
                        $dataMigration,
                        $orgExport,
                        $theme,
                        $wxrImport,
                        $urlRedirect,
                    ];

                    foreach ((new ModuleRegistry())->modules() as $module) {
                        foreach ($module->routeRegistrars($container) as $registrar) {
                            $registrars[] = $registrar;
                        }
                    }

                    return $registrars;
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
                    $duplicateEntityPermalink = $container->get(DuplicateEntityPermalinkExceptionHandler::class);
                    $textFieldNotFound = $container->get(TextFieldNotFoundExceptionHandler::class);
                    $blocksFieldNotFound = $container->get(BlocksFieldNotFoundExceptionHandler::class);
                    $intFieldNotFound = $container->get(IntFieldNotFoundExceptionHandler::class);
                    $enumFieldNotFound = $container->get(EnumFieldNotFoundExceptionHandler::class);
                    $boolFieldNotFound = $container->get(BoolFieldNotFoundExceptionHandler::class);
                    $datetimeFieldNotFound = $container->get(DateTimeFieldNotFoundExceptionHandler::class);
                    $fieldKeyNotRegistered = $container->get(FieldKeyNotRegisteredExceptionHandler::class);
                    $fieldTypeMismatch = $container->get(FieldTypeMismatchExceptionHandler::class);
                    $tagNotFound = $container->get(TagNotFoundExceptionHandler::class);
                    $tagSlugConflict = $container->get(TagSlugConflictExceptionHandler::class);
                    $entityTagAlreadyAttached = $container->get(EntityTagAlreadyAttachedExceptionHandler::class);
                    $entityTagNotAttached = $container->get(EntityTagNotAttachedExceptionHandler::class);
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
                    $mediaNotFound = $container->get(MediaNotFoundExceptionHandler::class);
                    $mediaInUse = $container->get(MediaInUseExceptionHandler::class);
                    $navigationItemNotFound = $container->get(NavigationItemNotFoundExceptionHandler::class);
                    $menuNotFound = $container->get(MenuNotFoundExceptionHandler::class);
                    $widgetNotFound = $container->get(WidgetNotFoundExceptionHandler::class);
                    $webhookNotFound = $container->get(WebhookNotFoundExceptionHandler::class);
                    $previewTokenNotFound = $container->get(PreviewTokenNotFoundExceptionHandler::class);
                    $userNotFound = $container->get(UserNotFoundExceptionHandler::class);
                    $userEmailConflict = $container->get(UserEmailConflictExceptionHandler::class);
                    $cannotDeleteSelf = $container->get(CannotDeleteSelfExceptionHandler::class);
                    $invalidUserRole = $container->get(InvalidUserRoleExceptionHandler::class);
                    $invalidCurrentPassword = $container->get(InvalidCurrentPasswordExceptionHandler::class);
                    $emailVerificationToken = $container->get(EmailVerificationTokenExceptionHandler::class);
                    $invalidInviteToken = $container->get(InvalidInviteTokenExceptionHandler::class);
                    $invalidResetToken = $container->get(InvalidPasswordResetTokenExceptionHandler::class);
                    $commentNotFound = $container->get(CommentNotFoundExceptionHandler::class);
                    $organizationNotFound = $container->get(OrganizationNotFoundExceptionHandler::class);
                    $organizationSlugConflict = $container->get(OrganizationSlugConflictExceptionHandler::class);
                    $featureNotEntitled = $container->get(FeatureNotEntitledExceptionHandler::class);
                    $notificationChannelNotFound = $container->get(NotificationChannelNotFoundExceptionHandler::class);
                    $themeNotFound = $container->get(ThemeNotFoundExceptionHandler::class);

                    foreach ([
                        $entityTypeNotFound,
                        $entityTypeHasEntities,
                        $entityTypeSlugConflict,
                        $fieldDefNotFound,
                        $fieldDefConflict,
                        $entityNotFound,
                        $duplicateEntitySlug,
                        $duplicateEntityPermalink,
                        $textFieldNotFound,
                        $blocksFieldNotFound,
                        $intFieldNotFound,
                        $enumFieldNotFound,
                        $boolFieldNotFound,
                        $datetimeFieldNotFound,
                        $fieldKeyNotRegistered,
                        $fieldTypeMismatch,
                        $tagNotFound,
                        $tagSlugConflict,
                        $entityTagAlreadyAttached,
                        $entityTagNotAttached,
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
                        $mediaNotFound,
                        $mediaInUse,
                        $navigationItemNotFound,
                        $menuNotFound,
                        $widgetNotFound,
                        $webhookNotFound,
                        $previewTokenNotFound,
                        $userNotFound,
                        $userEmailConflict,
                        $cannotDeleteSelf,
                        $invalidUserRole,
                        $invalidCurrentPassword,
                        $emailVerificationToken,
                        $invalidInviteToken,
                        $invalidResetToken,
                        $commentNotFound,
                        $organizationNotFound,
                        $organizationSlugConflict,
                        $featureNotEntitled,
                        $notificationChannelNotFound,
                        $themeNotFound,
                    ] as $handler) {
                        if (!$handler instanceof DomainExceptionHandlerInterface) {
                            throw new LogicException('Exception handler service is invalid.');
                        }
                    }

                    $handlers = [
                        $entityTypeNotFound,
                        $entityTypeHasEntities,
                        $entityTypeSlugConflict,
                        $fieldDefNotFound,
                        $fieldDefConflict,
                        $entityNotFound,
                        $duplicateEntitySlug,
                        $duplicateEntityPermalink,
                        $textFieldNotFound,
                        $blocksFieldNotFound,
                        $intFieldNotFound,
                        $enumFieldNotFound,
                        $boolFieldNotFound,
                        $datetimeFieldNotFound,
                        $fieldKeyNotRegistered,
                        $fieldTypeMismatch,
                        $tagNotFound,
                        $tagSlugConflict,
                        $entityTagAlreadyAttached,
                        $entityTagNotAttached,
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
                        $mediaNotFound,
                        $mediaInUse,
                        $navigationItemNotFound,
                        $menuNotFound,
                        $widgetNotFound,
                        $webhookNotFound,
                        $previewTokenNotFound,
                        $userNotFound,
                        $userEmailConflict,
                        $cannotDeleteSelf,
                        $invalidUserRole,
                        $invalidCurrentPassword,
                        $emailVerificationToken,
                        $invalidInviteToken,
                        $invalidResetToken,
                        $commentNotFound,
                        $organizationNotFound,
                        $organizationSlugConflict,
                        $featureNotEntitled,
                        $notificationChannelNotFound,
                        $themeNotFound,
                    ];

                    foreach ((new ModuleRegistry())->modules() as $module) {
                        foreach ($module->exceptionHandlers($container) as $handler) {
                            $handlers[] = $handler;
                        }
                    }

                    return $handlers;
                },
            );
    }
}
