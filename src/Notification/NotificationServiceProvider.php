<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Notification\Channel\ChatWorkChannel;
use NeNeRecords\Notification\Channel\DiscordChannel;
use NeNeRecords\Notification\Channel\EmailChannel;
use NeNeRecords\Notification\Channel\SlackChannel;
use NeNeRecords\Notification\Channel\WebhookChannel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class NotificationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        // ── Repository ────────────────────────────────────────────────────────

        $builder->set(
            NotificationChannelRepositoryInterface::class,
            static function (ContainerInterface $c): NotificationChannelRepositoryInterface {
                $query = $c->get(DatabaseQueryExecutorInterface::class);
                if (!$query instanceof DatabaseQueryExecutorInterface) {
                    throw new LogicException('Database query executor service is invalid.');
                }

                $orgId = $c->get('nene-records.org_id_holder');
                if (!$orgId instanceof RequestScopedHolder) {
                    throw new LogicException('Org ID holder service is invalid.');
                }

                $clock = $c->get(ClockInterface::class);
                if (!$clock instanceof ClockInterface) {
                    throw new LogicException('ClockInterface service is invalid.');
                }

                return new PdoNotificationChannelRepository($query, $orgId, $clock);
            },
        );

        // ── Channels ──────────────────────────────────────────────────────────

        $builder->set(
            EmailChannel::class,
            static function (ContainerInterface $c): EmailChannel {
                $mailer = $c->get(MailerInterface::class);
                if (!$mailer instanceof MailerInterface) {
                    throw new LogicException('MailerInterface service is invalid.');
                }

                return new EmailChannel($mailer);
            },
        );

        $builder->set(SlackChannel::class, static fn (): SlackChannel => new SlackChannel());
        $builder->set(DiscordChannel::class, static fn (): DiscordChannel => new DiscordChannel());
        $builder->set(ChatWorkChannel::class, static fn (): ChatWorkChannel => new ChatWorkChannel());
        $builder->set(
            WebhookChannel::class,
            static function (ContainerInterface $c): WebhookChannel {
                $clock = $c->get(ClockInterface::class);
                if (!$clock instanceof ClockInterface) {
                    throw new LogicException('ClockInterface service is invalid.');
                }

                return new WebhookChannel($clock);
            },
        );

        // ── Notifier ──────────────────────────────────────────────────────────

        $builder->set(
            NotifierInterface::class,
            static function (ContainerInterface $c): NotifierInterface {
                $repo = $c->get(NotificationChannelRepositoryInterface::class);
                if (!$repo instanceof NotificationChannelRepositoryInterface) {
                    throw new LogicException('NotificationChannelRepository service is invalid.');
                }

                $email = $c->get(EmailChannel::class);
                $slack = $c->get(SlackChannel::class);
                $discord = $c->get(DiscordChannel::class);
                $chatWork = $c->get(ChatWorkChannel::class);
                $webhook = $c->get(WebhookChannel::class);

                if (!$email instanceof EmailChannel) {
                    throw new LogicException('EmailChannel service is invalid.');
                }
                if (!$slack instanceof SlackChannel) {
                    throw new LogicException('SlackChannel service is invalid.');
                }
                if (!$discord instanceof DiscordChannel) {
                    throw new LogicException('DiscordChannel service is invalid.');
                }
                if (!$chatWork instanceof ChatWorkChannel) {
                    throw new LogicException('ChatWorkChannel service is invalid.');
                }
                if (!$webhook instanceof WebhookChannel) {
                    throw new LogicException('WebhookChannel service is invalid.');
                }

                return new CompositeNotifier($repo, $email, $slack, $discord, $chatWork, $webhook);
            },
        );

        // ── Use Cases ─────────────────────────────────────────────────────────

        $builder->set(
            ListNotificationChannelsUseCaseInterface::class,
            static function (ContainerInterface $c): ListNotificationChannelsUseCaseInterface {
                $repo = $c->get(NotificationChannelRepositoryInterface::class);
                if (!$repo instanceof NotificationChannelRepositoryInterface) {
                    throw new LogicException('NotificationChannelRepository service is invalid.');
                }

                return new ListNotificationChannelsUseCase($repo);
            },
        );

        $builder->set(
            CreateNotificationChannelUseCaseInterface::class,
            static function (ContainerInterface $c): CreateNotificationChannelUseCaseInterface {
                $repo = $c->get(NotificationChannelRepositoryInterface::class);
                if (!$repo instanceof NotificationChannelRepositoryInterface) {
                    throw new LogicException('NotificationChannelRepository service is invalid.');
                }

                return new CreateNotificationChannelUseCase($repo);
            },
        );

        $builder->set(
            UpdateNotificationChannelUseCaseInterface::class,
            static function (ContainerInterface $c): UpdateNotificationChannelUseCaseInterface {
                $repo = $c->get(NotificationChannelRepositoryInterface::class);
                if (!$repo instanceof NotificationChannelRepositoryInterface) {
                    throw new LogicException('NotificationChannelRepository service is invalid.');
                }

                return new UpdateNotificationChannelUseCase($repo);
            },
        );

        $builder->set(
            DeleteNotificationChannelUseCaseInterface::class,
            static function (ContainerInterface $c): DeleteNotificationChannelUseCaseInterface {
                $repo = $c->get(NotificationChannelRepositoryInterface::class);
                if (!$repo instanceof NotificationChannelRepositoryInterface) {
                    throw new LogicException('NotificationChannelRepository service is invalid.');
                }

                return new DeleteNotificationChannelUseCase($repo);
            },
        );

        $builder->set(
            TestNotificationChannelUseCaseInterface::class,
            static function (ContainerInterface $c): TestNotificationChannelUseCaseInterface {
                $repo = $c->get(NotificationChannelRepositoryInterface::class);
                $email = $c->get(EmailChannel::class);
                $slack = $c->get(SlackChannel::class);
                $discord = $c->get(DiscordChannel::class);
                $chatWork = $c->get(ChatWorkChannel::class);
                $webhook = $c->get(WebhookChannel::class);

                if (!$repo instanceof NotificationChannelRepositoryInterface
                    || !$email instanceof EmailChannel
                    || !$slack instanceof SlackChannel
                    || !$discord instanceof DiscordChannel
                    || !$chatWork instanceof ChatWorkChannel
                    || !$webhook instanceof WebhookChannel
                ) {
                    throw new LogicException('Notification channel services are invalid.');
                }

                return new TestNotificationChannelUseCase($repo, $email, $slack, $discord, $chatWork, $webhook);
            },
        );

        // ── Handlers ──────────────────────────────────────────────────────────

        $builder->set(
            ListNotificationChannelsHandler::class,
            static function (ContainerInterface $c): ListNotificationChannelsHandler {
                $useCase = $c->get(ListNotificationChannelsUseCaseInterface::class);
                $response = $c->get(JsonResponseFactory::class);
                if (!$useCase instanceof ListNotificationChannelsUseCaseInterface) {
                    throw new LogicException('ListNotificationChannels use case service is invalid.');
                }
                if (!$response instanceof JsonResponseFactory) {
                    throw new LogicException('JsonResponseFactory service is invalid.');
                }

                return new ListNotificationChannelsHandler($useCase, $response);
            },
        );

        $builder->set(
            CreateNotificationChannelHandler::class,
            static function (ContainerInterface $c): CreateNotificationChannelHandler {
                $useCase = $c->get(CreateNotificationChannelUseCaseInterface::class);
                $response = $c->get(JsonResponseFactory::class);
                if (!$useCase instanceof CreateNotificationChannelUseCaseInterface) {
                    throw new LogicException('CreateNotificationChannel use case service is invalid.');
                }
                if (!$response instanceof JsonResponseFactory) {
                    throw new LogicException('JsonResponseFactory service is invalid.');
                }

                return new CreateNotificationChannelHandler($useCase, $response);
            },
        );

        $builder->set(
            UpdateNotificationChannelHandler::class,
            static function (ContainerInterface $c): UpdateNotificationChannelHandler {
                $useCase = $c->get(UpdateNotificationChannelUseCaseInterface::class);
                $response = $c->get(JsonResponseFactory::class);
                if (!$useCase instanceof UpdateNotificationChannelUseCaseInterface) {
                    throw new LogicException('UpdateNotificationChannel use case service is invalid.');
                }
                if (!$response instanceof JsonResponseFactory) {
                    throw new LogicException('JsonResponseFactory service is invalid.');
                }

                return new UpdateNotificationChannelHandler($useCase, $response);
            },
        );

        $builder->set(
            DeleteNotificationChannelHandler::class,
            static function (ContainerInterface $c): DeleteNotificationChannelHandler {
                $useCase = $c->get(DeleteNotificationChannelUseCaseInterface::class);
                $responseFactory = $c->get(ResponseFactoryInterface::class);
                if (!$useCase instanceof DeleteNotificationChannelUseCaseInterface) {
                    throw new LogicException('DeleteNotificationChannel use case service is invalid.');
                }
                if (!$responseFactory instanceof ResponseFactoryInterface) {
                    throw new LogicException('ResponseFactory service is invalid.');
                }

                return new DeleteNotificationChannelHandler($useCase, $responseFactory);
            },
        );

        $builder->set(
            TestNotificationChannelHandler::class,
            static function (ContainerInterface $c): TestNotificationChannelHandler {
                $useCase = $c->get(TestNotificationChannelUseCaseInterface::class);
                $response = $c->get(JsonResponseFactory::class);
                if (!$useCase instanceof TestNotificationChannelUseCaseInterface) {
                    throw new LogicException('TestNotificationChannel use case service is invalid.');
                }
                if (!$response instanceof JsonResponseFactory) {
                    throw new LogicException('JsonResponseFactory service is invalid.');
                }

                return new TestNotificationChannelHandler($useCase, $response);
            },
        );

        // ── Route Registrar ───────────────────────────────────────────────────

        $builder->set(
            NotificationRouteRegistrar::class,
            static function (ContainerInterface $c): NotificationRouteRegistrar {
                return new NotificationRouteRegistrar(
                    $c->get(ListNotificationChannelsHandler::class),
                    $c->get(CreateNotificationChannelHandler::class),
                    $c->get(UpdateNotificationChannelHandler::class),
                    $c->get(DeleteNotificationChannelHandler::class),
                    $c->get(TestNotificationChannelHandler::class),
                );
            },
        );

        // ── Exception Handler ─────────────────────────────────────────────────

        $builder->set(
            NotificationChannelNotFoundExceptionHandler::class,
            static function (ContainerInterface $c): NotificationChannelNotFoundExceptionHandler {
                $factory = $c->get(ProblemDetailsResponseFactory::class);
                if (!$factory instanceof ProblemDetailsResponseFactory) {
                    throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                }

                return new NotificationChannelNotFoundExceptionHandler($factory);
            },
        );
    }
}
