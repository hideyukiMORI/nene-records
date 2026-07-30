<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final class ConnectTokenServiceResolver
{
    /**
     * Reads the `{service}` path parameter and maps it onto the allow-list.
     *
     * An unknown value is a 404, not a 422: the URL names a slot that does not exist.
     * The rejected value is echoed back only after being trimmed to a short, harmless
     * shape — a Problem Details body is not a place to reflect arbitrary input.
     *
     * @throws ConnectTokenNotFoundException
     */
    public static function fromPath(ServerRequestInterface $request): ConnectTokenService
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $raw = is_array($parameters) ? (string) ($parameters['service'] ?? '') : '';
        $service = ConnectTokenService::tryFrom($raw);

        if ($service === null) {
            throw new ConnectTokenNotFoundException(
                (string) preg_replace('/[^A-Za-z0-9_-]/', '', substr($raw, 0, 32)),
            );
        }

        return $service;
    }
}
