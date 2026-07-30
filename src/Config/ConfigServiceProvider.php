<?php

declare(strict_types=1);

namespace NeNeRecords\Config;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Wires the at-rest encryption used for configuration secrets (#1029).
 *
 * Registered even when `NENE_RECORDS_CONFIG_KEY` is unset: the resolver reads the
 * environment lazily, so a deployment without a key still boots and only fails when
 * something actually tries to store or read a secret. Refusing at construction time would
 * take the whole application down over a feature most tenants never enable.
 */
final readonly class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ConfigKeyResolverInterface::class,
                static fn (ContainerInterface $container): ConfigKeyResolverInterface => new EnvConfigKeyResolver(),
            )
            ->set(
                ConfigCipherInterface::class,
                static function (ContainerInterface $container): ConfigCipherInterface {
                    $keys = $container->get(ConfigKeyResolverInterface::class);

                    if (!$keys instanceof ConfigKeyResolverInterface) {
                        throw new \LogicException('Config key resolver service is invalid.');
                    }

                    return new AesGcmConfigCipher($keys);
                },
            );
    }
}
