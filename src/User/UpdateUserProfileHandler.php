<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateUserProfileHandler
{
    public function __construct(
        private UpdateUserProfileUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id         = (int) ($parameters['id'] ?? 0);

        $body        = JsonRequestBodyParser::parse($request);
        $displayName = array_key_exists('display_name', $body) ? ($body['display_name'] === null ? null : (string) $body['display_name']) : null;
        $fullName    = array_key_exists('full_name', $body) ? ($body['full_name'] === null ? null : (string) $body['full_name']) : null;
        $jobTitle    = array_key_exists('job_title', $body) ? ($body['job_title'] === null ? null : (string) $body['job_title']) : null;

        $output = $this->useCase->execute(new UpdateUserProfileInput(
            userId: $id,
            displayName: $displayName,
            fullName: $fullName,
            jobTitle: $jobTitle,
        ));

        return $this->response->create([
            'user_id'      => $output->userId,
            'display_name' => $output->displayName,
            'full_name'    => $output->fullName,
            'job_title'    => $output->jobTitle,
        ]);
    }
}
