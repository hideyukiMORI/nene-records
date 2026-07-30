<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Middleware\RateLimitStorageInterface;
use NeNeRecords\OrgConnect\ConnectTokenProviderInterface;
use NeNeRecords\PublicRecord\ContactFormSchemaProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

final readonly class ContactSubmissionServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ContactSubmissionSenderInterface::class,
                static function (ContainerInterface $container): ContactSubmissionSenderInterface {
                    $tokens = $container->get(ConnectTokenProviderInterface::class);

                    if (!$tokens instanceof ConnectTokenProviderInterface) {
                        throw new LogicException('Connect token provider service is invalid.');
                    }

                    // Same operator-configured host as the schema read. Unset is a normal state;
                    // the sender then fails visibly rather than pretending to deliver.
                    $baseUrl = getenv('NENE_RECORDS_CONTACT_BASE_URL');

                    return new HttpContactSubmissionSender(
                        is_string($baseUrl) ? $baseUrl : null,
                        $tokens,
                    );
                },
            )
            ->set(
                SubmitContactFormHandler::class,
                static function (ContainerInterface $container): SubmitContactFormHandler {
                    $schemas = $container->get(ContactFormSchemaProviderInterface::class);
                    $sender = $container->get(ContactSubmissionSenderInterface::class);
                    $rateLimit = $container->get(RateLimitStorageInterface::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    $responses = $container->get(ResponseFactoryInterface::class);
                    $clock = $container->get(ClockInterface::class);
                    $logger = $container->get(LoggerInterface::class);

                    if (!$schemas instanceof ContactFormSchemaProviderInterface) {
                        throw new LogicException('Contact form schema provider service is invalid.');
                    }

                    if (!$sender instanceof ContactSubmissionSenderInterface) {
                        throw new LogicException('Contact submission sender service is invalid.');
                    }

                    if (!$rateLimit instanceof RateLimitStorageInterface) {
                        throw new LogicException('Rate limit storage service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    if (!$responses instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    if (!$logger instanceof LoggerInterface) {
                        throw new LogicException('Logger service is invalid.');
                    }

                    return new SubmitContactFormHandler(
                        $schemas,
                        $sender,
                        $rateLimit,
                        $problemDetails,
                        $responses,
                        $clock,
                        $logger,
                    );
                },
            )
            ->set(
                'nene-records.route_registrar.contact_submission',
                static function (ContainerInterface $container): ContactSubmissionRouteRegistrar {
                    $submit = $container->get(SubmitContactFormHandler::class);

                    if (!$submit instanceof SubmitContactFormHandler) {
                        throw new LogicException('SubmitContactForm handler service is invalid.');
                    }

                    return new ContactSubmissionRouteRegistrar($submit);
                },
            );
    }
}
