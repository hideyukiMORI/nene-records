<?php

declare(strict_types=1);

/**
 * Webhook delivery worker (#285).
 *
 * Drains the `webhook_deliveries` queue: sends due deliveries, retries with backoff,
 * and marks them failed once attempts are exhausted. Intended to be run on a schedule
 * (e.g. cron every minute) or in a simple loop.
 *
 * Usage:
 *   php tools/webhook-worker.php [--limit=N] [--loop] [--interval=SECONDS]
 *
 * Examples:
 *   php tools/webhook-worker.php                 # process up to 50 due deliveries once
 *   php tools/webhook-worker.php --limit=200     # process up to 200
 *   php tools/webhook-worker.php --loop          # run continuously (interval 10s)
 *   docker compose exec app php tools/webhook-worker.php
 */

use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Webhook\WebhookDeliveryProcessor;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @param non-empty-string $name */
function option(string $name, ?string $default = null): ?string
{
    foreach ($GLOBALS['argv'] as $arg) {
        if (str_starts_with((string) $arg, "--{$name}=")) {
            return substr((string) $arg, strlen($name) + 3);
        }

        if ($arg === "--{$name}") {
            return '1';
        }
    }

    return $default;
}

$limit = max(1, (int) (option('limit') ?? '50'));
$loop = option('loop') !== null;
$interval = max(1, (int) (option('interval') ?? '10'));

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();
$processor = $container->get(WebhookDeliveryProcessor::class);
assert($processor instanceof WebhookDeliveryProcessor);

do {
    $summary = $processor->process($limit);

    if ($summary['processed'] > 0) {
        fwrite(STDOUT, sprintf(
            "[%s] processed=%d delivered=%d retried=%d failed=%d\n",
            date('c'),
            $summary['processed'],
            $summary['delivered'],
            $summary['retried'],
            $summary['failed'],
        ));
    }

    if ($loop) {
        sleep($interval);
    }
} while ($loop);
