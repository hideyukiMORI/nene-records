<?php

declare(strict_types=1);

namespace NeNeRecords\RateLimit;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Middleware\RateLimitStorageInterface;
use Nene2\Middleware\ThrottleMiddleware;
use Psr\Container\ContainerInterface;

final readonly class RateLimitServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                RateLimitStorageInterface::class,
                static function (ContainerInterface $container): RateLimitStorageInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoRateLimitStorage($query);
                },
            )
            ->set(
                ThrottleMiddleware::class,
                static function (ContainerInterface $container): ThrottleMiddleware {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    $storage = $container->get(RateLimitStorageInterface::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    if (!$storage instanceof RateLimitStorageInterface) {
                        throw new LogicException('Rate limit storage service is invalid.');
                    }

                    // 120 requests per minute per IP
                    return new ThrottleMiddleware(
                        $problemDetails,
                        $storage,
                        limit: 120,
                        windowSeconds: 60,
                    );
                },
            );
    }
}
