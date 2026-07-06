<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\ApplicationServiceProvider;
use NeNeRecords\Entitlement\EntitlementResolverInterface;
use NeNeRecords\Setting\DefaultSettingDefsSeederInterface;
use NeNeRecords\Setting\PdoDefaultSettingDefsSeeder;
use NeNeRecords\SystemConfig\SystemConfigRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class OrganizationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DefaultContentTypeSeederInterface::class,
                static function (ContainerInterface $c): DefaultContentTypeSeederInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoDefaultContentTypeSeeder($query);
                },
            )
            ->set(
                DefaultSettingDefsSeederInterface::class,
                static function (ContainerInterface $c): DefaultSettingDefsSeederInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoDefaultSettingDefsSeeder($query);
                },
            )
            ->set(
                OrganizationRepositoryInterface::class,
                static function (ContainerInterface $c): OrganizationRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('ClockInterface service is invalid.');
                    }

                    return new PdoOrganizationRepository($query, $clock);
                },
            )
            // ── Use cases ──────────────────────────────────────────────────────
            ->set(
                ListOrganizationsUseCaseInterface::class,
                static function (ContainerInterface $c): ListOrganizationsUseCaseInterface {
                    $repo = $c->get(OrganizationRepositoryInterface::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    return new ListOrganizationsUseCase($repo);
                },
            )
            ->set(
                GetOrganizationByIdUseCaseInterface::class,
                static function (ContainerInterface $c): GetOrganizationByIdUseCaseInterface {
                    $repo = $c->get(OrganizationRepositoryInterface::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    return new GetOrganizationByIdUseCase($repo);
                },
            )
            ->set(
                CreateOrganizationUseCaseInterface::class,
                static function (ContainerInterface $c): CreateOrganizationUseCaseInterface {
                    $repo   = $c->get(OrganizationRepositoryInterface::class);
                    $seeder = $c->get(DefaultContentTypeSeederInterface::class);
                    $settingDefsSeeder = $c->get(DefaultSettingDefsSeederInterface::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$seeder instanceof DefaultContentTypeSeederInterface) {
                        throw new LogicException('Default content type seeder service is invalid.');
                    }

                    if (!$settingDefsSeeder instanceof DefaultSettingDefsSeederInterface) {
                        throw new LogicException('Default setting defs seeder service is invalid.');
                    }

                    return new CreateOrganizationUseCase($repo, $seeder, $settingDefsSeeder);
                },
            )
            ->set(
                UpdateOrganizationUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateOrganizationUseCaseInterface {
                    $repo = $c->get(OrganizationRepositoryInterface::class);
                    $entitlements = $c->get(EntitlementResolverInterface::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$entitlements instanceof EntitlementResolverInterface) {
                        throw new LogicException('Entitlement resolver service is invalid.');
                    }

                    return new UpdateOrganizationUseCase($repo, $entitlements);
                },
            )
            ->set(
                DeleteOrganizationUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteOrganizationUseCaseInterface {
                    $repo = $c->get(OrganizationRepositoryInterface::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    return new DeleteOrganizationUseCase($repo);
                },
            )
            // ── Handlers ───────────────────────────────────────────────────────
            ->set(
                ListOrganizationsHandler::class,
                static function (ContainerInterface $c): ListOrganizationsHandler {
                    $uc   = $c->get(ListOrganizationsUseCaseInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$uc instanceof ListOrganizationsUseCaseInterface) {
                        throw new LogicException('ListOrganizations use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListOrganizationsHandler($uc, $json);
                },
            )
            ->set(
                GetOrganizationByIdHandler::class,
                static function (ContainerInterface $c): GetOrganizationByIdHandler {
                    $uc   = $c->get(GetOrganizationByIdUseCaseInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$uc instanceof GetOrganizationByIdUseCaseInterface) {
                        throw new LogicException('GetOrganizationById use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetOrganizationByIdHandler($uc, $json);
                },
            )
            ->set(
                CreateOrganizationHandler::class,
                static function (ContainerInterface $c): CreateOrganizationHandler {
                    $uc   = $c->get(CreateOrganizationUseCaseInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$uc instanceof CreateOrganizationUseCaseInterface) {
                        throw new LogicException('CreateOrganization use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new CreateOrganizationHandler($uc, $json);
                },
            )
            ->set(
                UpdateOrganizationHandler::class,
                static function (ContainerInterface $c): UpdateOrganizationHandler {
                    $uc   = $c->get(UpdateOrganizationUseCaseInterface::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$uc instanceof UpdateOrganizationUseCaseInterface) {
                        throw new LogicException('UpdateOrganization use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new UpdateOrganizationHandler($uc, $json);
                },
            )
            ->set(
                DeleteOrganizationHandler::class,
                static function (ContainerInterface $c): DeleteOrganizationHandler {
                    $uc              = $c->get(DeleteOrganizationUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$uc instanceof DeleteOrganizationUseCaseInterface) {
                        throw new LogicException('DeleteOrganization use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new DeleteOrganizationHandler($uc, $responseFactory);
                },
            )
            // ── Exception handlers ─────────────────────────────────────────────
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
            // ── Route registrar ────────────────────────────────────────────────
            ->set(
                OrganizationRouteRegistrar::class,
                static function (ContainerInterface $c): OrganizationRouteRegistrar {
                    $list   = $c->get(ListOrganizationsHandler::class);
                    $get    = $c->get(GetOrganizationByIdHandler::class);
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
            )
            // ── On-demand TLS gate (subdomain SaaS) ─────────────────────────────
            ->set(
                TlsCheckHandler::class,
                static function (ContainerInterface $c): TlsCheckHandler {
                    $repo            = $c->get(OrganizationRepositoryInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $sysConfig       = $c->get(SystemConfigRepositoryInterface::class);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('OrganizationRepositoryInterface is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactoryInterface is invalid.');
                    }

                    // Same source as OrgResolverMiddleware's subdomain strategy.
                    $baseDomain = (string) (getenv('BASE_DOMAIN') ?: 'localhost');
                    if ($sysConfig instanceof SystemConfigRepositoryInterface) {
                        $baseDomain = $sysConfig->get('tenant_base_domain') ?: $baseDomain;
                    }

                    return new TlsCheckHandler($repo, $responseFactory, $baseDomain);
                },
            )
            ->set(
                TlsCheckRouteRegistrar::class,
                static function (ContainerInterface $c): TlsCheckRouteRegistrar {
                    $handler = $c->get(TlsCheckHandler::class);
                    if (!$handler instanceof TlsCheckHandler) {
                        throw new LogicException('TlsCheckHandler service is invalid.');
                    }

                    return new TlsCheckRouteRegistrar($handler);
                },
            )
            // ── Multi-tenant batch iteration (cron jobs) ────────────────────────
            ->set(
                OrganizationIterator::class,
                static function (ContainerInterface $c): OrganizationIterator {
                    $repo = $c->get(OrganizationRepositoryInterface::class);
                    $orgHolder = $c->get(ApplicationServiceProvider::ORG_ID_HOLDER);

                    if (!$repo instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('OrganizationRepositoryInterface is invalid.');
                    }

                    if (!$orgHolder instanceof RequestScopedHolder) {
                        throw new LogicException('Org ID holder service is invalid.');
                    }

                    /** @var RequestScopedHolder<int> $orgHolder */
                    return new OrganizationIterator($repo, $orgHolder);
                },
            );
    }
}
