<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Psr\Container\ContainerInterface;

final readonly class WebhookServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                WebhookRepositoryInterface::class,
                static function (ContainerInterface $container): WebhookRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $orgId = $container->get('nene-records.org_id_holder');
                    if (!$orgId instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    return new PdoWebhookRepository($query, $orgId);
                },
            )
            ->set(
                WebhookDeliveryRepositoryInterface::class,
                static function (ContainerInterface $container): WebhookDeliveryRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoWebhookDeliveryRepository($query);
                },
            )
            ->set(
                WebhookSenderInterface::class,
                static fn (): WebhookSenderInterface => new CurlWebhookSender(),
            )
            ->set(
                WebhookDispatcherInterface::class,
                static function (ContainerInterface $container): WebhookDispatcherInterface {
                    $repo = $container->get(WebhookRepositoryInterface::class);
                    $deliveries = $container->get(WebhookDeliveryRepositoryInterface::class);

                    if (!$repo instanceof WebhookRepositoryInterface) {
                        throw new LogicException('Webhook repository service is invalid.');
                    }

                    if (!$deliveries instanceof WebhookDeliveryRepositoryInterface) {
                        throw new LogicException('Webhook delivery repository service is invalid.');
                    }

                    return new QueueingWebhookDispatcher($repo, $deliveries);
                },
            )
            ->set(
                WebhookDeliveryProcessor::class,
                static function (ContainerInterface $container): WebhookDeliveryProcessor {
                    $deliveries = $container->get(WebhookDeliveryRepositoryInterface::class);
                    $sender = $container->get(WebhookSenderInterface::class);

                    if (!$deliveries instanceof WebhookDeliveryRepositoryInterface) {
                        throw new LogicException('Webhook delivery repository service is invalid.');
                    }

                    if (!$sender instanceof WebhookSenderInterface) {
                        throw new LogicException('Webhook sender service is invalid.');
                    }

                    return new WebhookDeliveryProcessor($deliveries, $sender);
                },
            )
            ->set(
                ListWebhooksUseCaseInterface::class,
                static function (ContainerInterface $container): ListWebhooksUseCaseInterface {
                    $repo = $container->get(WebhookRepositoryInterface::class);

                    if (!$repo instanceof WebhookRepositoryInterface) {
                        throw new LogicException('Webhook repository service is invalid.');
                    }

                    return new ListWebhooksUseCase($repo);
                },
            )
            ->set(
                GetWebhookByIdUseCaseInterface::class,
                static function (ContainerInterface $container): GetWebhookByIdUseCaseInterface {
                    $repo = $container->get(WebhookRepositoryInterface::class);

                    if (!$repo instanceof WebhookRepositoryInterface) {
                        throw new LogicException('Webhook repository service is invalid.');
                    }

                    return new GetWebhookByIdUseCase($repo);
                },
            )
            ->set(
                CreateWebhookUseCaseInterface::class,
                static function (ContainerInterface $container): CreateWebhookUseCaseInterface {
                    $repo = $container->get(WebhookRepositoryInterface::class);

                    if (!$repo instanceof WebhookRepositoryInterface) {
                        throw new LogicException('Webhook repository service is invalid.');
                    }

                    return new CreateWebhookUseCase($repo);
                },
            )
            ->set(
                UpdateWebhookUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateWebhookUseCaseInterface {
                    $repo = $container->get(WebhookRepositoryInterface::class);

                    if (!$repo instanceof WebhookRepositoryInterface) {
                        throw new LogicException('Webhook repository service is invalid.');
                    }

                    return new UpdateWebhookUseCase($repo);
                },
            )
            ->set(
                DeleteWebhookUseCaseInterface::class,
                static function (ContainerInterface $container): DeleteWebhookUseCaseInterface {
                    $repo = $container->get(WebhookRepositoryInterface::class);

                    if (!$repo instanceof WebhookRepositoryInterface) {
                        throw new LogicException('Webhook repository service is invalid.');
                    }

                    return new DeleteWebhookUseCase($repo);
                },
            )
            ->set(
                ListWebhooksHandler::class,
                static function (ContainerInterface $container): ListWebhooksHandler {
                    $useCase = $container->get(ListWebhooksUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListWebhooksUseCaseInterface) {
                        throw new LogicException('ListWebhooks use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListWebhooksHandler($useCase, $response);
                },
            )
            ->set(
                GetWebhookByIdHandler::class,
                static function (ContainerInterface $container): GetWebhookByIdHandler {
                    $useCase = $container->get(GetWebhookByIdUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetWebhookByIdUseCaseInterface) {
                        throw new LogicException('GetWebhookById use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetWebhookByIdHandler($useCase, $response);
                },
            )
            ->set(
                CreateWebhookHandler::class,
                static function (ContainerInterface $container): CreateWebhookHandler {
                    $useCase = $container->get(CreateWebhookUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateWebhookUseCaseInterface) {
                        throw new LogicException('CreateWebhook use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateWebhookHandler($useCase, $response);
                },
            )
            ->set(
                UpdateWebhookHandler::class,
                static function (ContainerInterface $container): UpdateWebhookHandler {
                    $useCase = $container->get(UpdateWebhookUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateWebhookUseCaseInterface) {
                        throw new LogicException('UpdateWebhook use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateWebhookHandler($useCase, $response);
                },
            )
            ->set(
                DeleteWebhookHandler::class,
                static function (ContainerInterface $container): DeleteWebhookHandler {
                    $useCase = $container->get(DeleteWebhookUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof DeleteWebhookUseCaseInterface) {
                        throw new LogicException('DeleteWebhook use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteWebhookHandler($useCase, $response);
                },
            )
            ->set(
                ProcessWebhookDeliveriesHandler::class,
                static function (ContainerInterface $container): ProcessWebhookDeliveriesHandler {
                    $processor = $container->get(WebhookDeliveryProcessor::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$processor instanceof WebhookDeliveryProcessor) {
                        throw new LogicException('Webhook delivery processor service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ProcessWebhookDeliveriesHandler($processor, $response);
                },
            )
            ->set(
                WebhookNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): WebhookNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new WebhookNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.webhook',
                static function (ContainerInterface $container): WebhookRouteRegistrar {
                    $list = $container->get(ListWebhooksHandler::class);
                    $getById = $container->get(GetWebhookByIdHandler::class);
                    $create = $container->get(CreateWebhookHandler::class);
                    $update = $container->get(UpdateWebhookHandler::class);
                    $delete = $container->get(DeleteWebhookHandler::class);
                    $processDeliveries = $container->get(ProcessWebhookDeliveriesHandler::class);

                    if (!$list instanceof ListWebhooksHandler) {
                        throw new LogicException('ListWebhooks handler service is invalid.');
                    }

                    if (!$getById instanceof GetWebhookByIdHandler) {
                        throw new LogicException('GetWebhookById handler service is invalid.');
                    }

                    if (!$create instanceof CreateWebhookHandler) {
                        throw new LogicException('CreateWebhook handler service is invalid.');
                    }

                    if (!$update instanceof UpdateWebhookHandler) {
                        throw new LogicException('UpdateWebhook handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteWebhookHandler) {
                        throw new LogicException('DeleteWebhook handler service is invalid.');
                    }

                    if (!$processDeliveries instanceof ProcessWebhookDeliveriesHandler) {
                        throw new LogicException('ProcessWebhookDeliveries handler service is invalid.');
                    }

                    return new WebhookRouteRegistrar($list, $getById, $create, $update, $delete, $processDeliveries);
                },
            );
    }
}
