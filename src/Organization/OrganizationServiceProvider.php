<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class OrganizationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrganizationRepositoryInterface::class,
                static function (ContainerInterface $c): OrganizationRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoOrganizationRepository($query);
                },
            )
            ->set(
                ListOrganizationsHandler::class,
                static function (ContainerInterface $c): ListOrganizationsHandler {
                    $repo = $c->get(OrganizationRepositoryInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListOrganizationsHandler($repo, $json);
                },
            )
            ->set(
                GetOrganizationByIdHandler::class,
                static function (ContainerInterface $c): GetOrganizationByIdHandler {
                    $repo = $c->get(OrganizationRepositoryInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetOrganizationByIdHandler($repo, $json);
                },
            )
            ->set(
                CreateOrganizationHandler::class,
                static function (ContainerInterface $c): CreateOrganizationHandler {
                    $repo = $c->get(OrganizationRepositoryInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateOrganizationHandler($repo, $json);
                },
            )
            ->set(
                UpdateOrganizationHandler::class,
                static function (ContainerInterface $c): UpdateOrganizationHandler {
                    $repo = $c->get(OrganizationRepositoryInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateOrganizationHandler($repo, $json);
                },
            )
            ->set(
                DeleteOrganizationHandler::class,
                static function (ContainerInterface $c): DeleteOrganizationHandler {
                    $repo = $c->get(OrganizationRepositoryInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteOrganizationHandler($repo, $responseFactory);
                },
            )
            ->set(
                OrganizationNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): OrganizationNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new OrganizationNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                OrganizationSlugConflictExceptionHandler::class,
                static function (ContainerInterface $c): OrganizationSlugConflictExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new OrganizationSlugConflictExceptionHandler($problemDetails);
                },
            )
            ->set(
                OrganizationRouteRegistrar::class,
                static function (ContainerInterface $c): OrganizationRouteRegistrar {
                    $list = $c->get(ListOrganizationsHandler::class);
                    $get = $c->get(GetOrganizationByIdHandler::class);
                    $create = $c->get(CreateOrganizationHandler::class);
                    $update = $c->get(UpdateOrganizationHandler::class);
                    $delete = $c->get(DeleteOrganizationHandler::class);

                    if (!$list instanceof ListOrganizationsHandler) {
                        throw new LogicException('ListOrganizations handler service is invalid.');
                    }

                    if (!$get instanceof GetOrganizationByIdHandler) {
                        throw new LogicException('GetOrganizationById handler service is invalid.');
                    }

                    if (!$create instanceof CreateOrganizationHandler) {
                        throw new LogicException('CreateOrganization handler service is invalid.');
                    }

                    if (!$update instanceof UpdateOrganizationHandler) {
                        throw new LogicException('UpdateOrganization handler service is invalid.');
                    }

                    if (!$delete instanceof DeleteOrganizationHandler) {
                        throw new LogicException('DeleteOrganization handler service is invalid.');
                    }

                    return new OrganizationRouteRegistrar($list, $get, $create, $update, $delete);
                },
            );
    }
}
