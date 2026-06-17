<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\TextField\TextFieldRepositoryInterface;
use NeNeRecords\Webhook\WebhookDispatcherInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class EntityServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                EntityRepositoryInterface::class,
                static function (ContainerInterface $c): EntityRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $c->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoEntityRepository($query, $orgId);
                },
            )
            ->set(
                GetEntityByIdUseCaseInterface::class,
                static function (ContainerInterface $c): GetEntityByIdUseCaseInterface {
                    $repository = $c->get(EntityRepositoryInterface::class);

                    if (!$repository instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new GetEntityByIdUseCase($repository);
                },
            )
            ->set(
                GetEntityByIdHandler::class,
                static function (ContainerInterface $c): GetEntityByIdHandler {
                    $useCase = $c->get(GetEntityByIdUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetEntityByIdUseCaseInterface) {
                        throw new LogicException('GetEntityById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetEntityByIdHandler($useCase, $response);
                },
            )
            ->set(
                CreateEntityUseCaseInterface::class,
                static function (ContainerInterface $c): CreateEntityUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $entityTypes = $c->get(EntityTypeRepositoryInterface::class);
                    $webhooks = $c->get(WebhookDispatcherInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$webhooks instanceof WebhookDispatcherInterface) {
                        throw new LogicException('Webhook dispatcher service is invalid.');
                    }

                    return new CreateEntityUseCase($entities, $entityTypes, $webhooks);
                },
            )
            ->set(
                CreateEntityHandler::class,
                static function (ContainerInterface $c): CreateEntityHandler {
                    $useCase = $c->get(CreateEntityUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateEntityUseCaseInterface) {
                        throw new LogicException('CreateEntity use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateEntityHandler($useCase, $response);
                },
            )
            ->set(
                DeleteEntityUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteEntityUseCaseInterface {
                    $repository = $c->get(EntityRepositoryInterface::class);
                    $webhooks = $c->get(WebhookDispatcherInterface::class);

                    if (!$repository instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$webhooks instanceof WebhookDispatcherInterface) {
                        throw new LogicException('Webhook dispatcher service is invalid.');
                    }

                    return new DeleteEntityUseCase($repository, $webhooks);
                },
            )
            ->set(
                DeleteEntityHandler::class,
                static function (ContainerInterface $c): DeleteEntityHandler {
                    $useCase = $c->get(DeleteEntityUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteEntityUseCaseInterface) {
                        throw new LogicException('DeleteEntity use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteEntityHandler($useCase, $responseFactory);
                },
            )
            ->set(
                ListEntitiesUseCaseInterface::class,
                static function (ContainerInterface $c): ListEntitiesUseCaseInterface {
                    $repository = $c->get(EntityRepositoryInterface::class);

                    if (!$repository instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new ListEntitiesUseCase($repository);
                },
            )
            ->set(
                ListEntitiesHandler::class,
                static function (ContainerInterface $c): ListEntitiesHandler {
                    $useCase = $c->get(ListEntitiesUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListEntitiesUseCaseInterface) {
                        throw new LogicException('ListEntities use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    $textFields = $c->get(TextFieldRepositoryInterface::class);
                    $settings = $c->get(SettingRepositoryInterface::class);

                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    if (!$settings instanceof SettingRepositoryInterface) {
                        throw new LogicException('Setting repository service is invalid.');
                    }

                    return new ListEntitiesHandler(
                        $useCase,
                        $response,
                        new ExcerptResolver($textFields, $settings),
                    );
                },
            )
            ->set(
                UpdateEntityUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateEntityUseCaseInterface {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $entityTypes = $c->get(EntityTypeRepositoryInterface::class);
                    $webhooks = $c->get(WebhookDispatcherInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$webhooks instanceof WebhookDispatcherInterface) {
                        throw new LogicException('Webhook dispatcher service is invalid.');
                    }

                    return new UpdateEntityUseCase($entities, $entityTypes, $webhooks);
                },
            )
            ->set(
                UpdateEntityHandler::class,
                static function (ContainerInterface $c): UpdateEntityHandler {
                    $useCase = $c->get(UpdateEntityUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateEntityUseCaseInterface) {
                        throw new LogicException('UpdateEntity use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateEntityHandler($useCase, $response);
                },
            )
            ->set(
                DuplicateEntitySlugExceptionHandler::class,
                static function (ContainerInterface $c): DuplicateEntitySlugExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new DuplicateEntitySlugExceptionHandler($problemDetails);
                },
            )
            ->set(
                EntityNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): EntityNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new EntityNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                ListEntityRevisionsUseCaseInterface::class,
                static function (ContainerInterface $c): ListEntityRevisionsUseCaseInterface {
                    $repository = $c->get(EntityRepositoryInterface::class);

                    if (!$repository instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new ListEntityRevisionsUseCase($repository);
                },
            )
            ->set(
                ListEntityRevisionsHandler::class,
                static function (ContainerInterface $c): ListEntityRevisionsHandler {
                    $useCase = $c->get(ListEntityRevisionsUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListEntityRevisionsUseCaseInterface) {
                        throw new LogicException('ListEntityRevisions use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListEntityRevisionsHandler($useCase, $response);
                },
            )
            ->set(
                ScheduleEntityUseCaseInterface::class,
                static function (ContainerInterface $c): ScheduleEntityUseCaseInterface {
                    $repository = $c->get(EntityRepositoryInterface::class);

                    if (!$repository instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new ScheduleEntityUseCase($repository);
                },
            )
            ->set(
                ScheduleEntityHandler::class,
                static function (ContainerInterface $c): ScheduleEntityHandler {
                    $useCase = $c->get(ScheduleEntityUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ScheduleEntityUseCaseInterface) {
                        throw new LogicException('ScheduleEntity use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ScheduleEntityHandler($useCase, $response);
                },
            )
            ->set(
                UnscheduleEntityUseCaseInterface::class,
                static function (ContainerInterface $c): UnscheduleEntityUseCaseInterface {
                    $repository = $c->get(EntityRepositoryInterface::class);

                    if (!$repository instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new UnscheduleEntityUseCase($repository);
                },
            )
            ->set(
                UnscheduleEntityHandler::class,
                static function (ContainerInterface $c): UnscheduleEntityHandler {
                    $useCase = $c->get(UnscheduleEntityUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof UnscheduleEntityUseCaseInterface) {
                        throw new LogicException('UnscheduleEntity use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new UnscheduleEntityHandler($useCase, $responseFactory);
                },
            )
            ->set(
                ProcessScheduledPublishUseCaseInterface::class,
                static function (ContainerInterface $c): ProcessScheduledPublishUseCaseInterface {
                    $repository = $c->get(EntityRepositoryInterface::class);

                    if (!$repository instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new ProcessScheduledPublishUseCase($repository);
                },
            )
            ->set(
                ProcessScheduledPublishHandler::class,
                static function (ContainerInterface $c): ProcessScheduledPublishHandler {
                    $useCase = $c->get(ProcessScheduledPublishUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ProcessScheduledPublishUseCaseInterface) {
                        throw new LogicException('ProcessScheduledPublish use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ProcessScheduledPublishHandler($useCase, $response);
                },
            )
            ->set(
                ExportEntitiesHandler::class,
                static function (ContainerInterface $c): ExportEntitiesHandler {
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $textFields = $c->get(TextFieldRepositoryInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new ExportEntitiesHandler($entities, $textFields, $responseFactory);
                },
            )
            ->set(
                'nene-records.route_registrar.entity',
                static function (ContainerInterface $c): EntityRouteRegistrar {
                    $get = $c->get(GetEntityByIdHandler::class);
                    $create = $c->get(CreateEntityHandler::class);
                    $update = $c->get(UpdateEntityHandler::class);
                    $delete = $c->get(DeleteEntityHandler::class);
                    $list = $c->get(ListEntitiesHandler::class);
                    $listRevisions = $c->get(ListEntityRevisionsHandler::class);
                    $export = $c->get(ExportEntitiesHandler::class);
                    $schedule = $c->get(ScheduleEntityHandler::class);
                    $unschedule = $c->get(UnscheduleEntityHandler::class);
                    $processScheduled = $c->get(ProcessScheduledPublishHandler::class);

                    if (!$get instanceof GetEntityByIdHandler) {
                        throw new LogicException('GetEntityById handler service is invalid.');
                    }

                    if (!$create instanceof CreateEntityHandler) {
                        throw new LogicException('CreateEntity handler service is invalid.');
                    }

                    if (!$update instanceof UpdateEntityHandler) {
                        throw new LogicException('UpdateEntity handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteEntityHandler) {
                        throw new LogicException('DeleteEntity handler service is invalid.');
                    }

                    if (!$list instanceof ListEntitiesHandler) {
                        throw new LogicException('ListEntities handler service is invalid.');
                    }

                    if (!$listRevisions instanceof ListEntityRevisionsHandler) {
                        throw new LogicException('ListEntityRevisions handler service is invalid.');
                    }

                    if (!$export instanceof ExportEntitiesHandler) {
                        throw new LogicException('ExportEntities handler service is invalid.');
                    }

                    if (!$schedule instanceof ScheduleEntityHandler) {
                        throw new LogicException('ScheduleEntity handler service is invalid.');
                    }

                    if (!$unschedule instanceof UnscheduleEntityHandler) {
                        throw new LogicException('UnscheduleEntity handler service is invalid.');
                    }

                    if (!$processScheduled instanceof ProcessScheduledPublishHandler) {
                        throw new LogicException('ProcessScheduledPublish handler service is invalid.');
                    }

                    return new EntityRouteRegistrar($get, $create, $update, $delete, $list, $listRevisions, $export, $schedule, $unschedule, $processScheduled);
                },
            );
    }
}
