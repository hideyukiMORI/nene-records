<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityTag\EntityTagRepositoryInterface;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\Tag\TagRepositoryInterface;
use NeNeRecords\TextField\TextFieldRepositoryInterface;
use NeNeRecords\UrlRedirect\UrlRedirectRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class WxrImportServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                WxrImportExecutor::class,
                static function (ContainerInterface $c): WxrImportExecutor {
                    $entityTypes = $c->get(EntityTypeRepositoryInterface::class);
                    $fieldDefs = $c->get(FieldDefRepositoryInterface::class);
                    $entities = $c->get(EntityRepositoryInterface::class);
                    $textFields = $c->get(TextFieldRepositoryInterface::class);
                    $tags = $c->get(TagRepositoryInterface::class);
                    $entityTags = $c->get(EntityTagRepositoryInterface::class);
                    $redirects = $c->get(UrlRedirectRepositoryInterface::class);

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }
                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field def repository service is invalid.');
                    }
                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }
                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }
                    if (!$tags instanceof TagRepositoryInterface) {
                        throw new LogicException('Tag repository service is invalid.');
                    }
                    if (!$entityTags instanceof EntityTagRepositoryInterface) {
                        throw new LogicException('Entity tag repository service is invalid.');
                    }
                    if (!$redirects instanceof UrlRedirectRepositoryInterface) {
                        throw new LogicException('URL redirect repository service is invalid.');
                    }

                    return new WxrImportExecutor(
                        $entityTypes,
                        $fieldDefs,
                        $entities,
                        $textFields,
                        $tags,
                        $entityTags,
                        $redirects,
                    );
                },
            )
            ->set(
                WxrImportHttpHandler::class,
                static function (ContainerInterface $c): WxrImportHttpHandler {
                    $executor = $c->get(WxrImportExecutor::class);
                    $json = $c->get(JsonResponseFactory::class);
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$executor instanceof WxrImportExecutor) {
                        throw new LogicException('WXR import executor service is invalid.');
                    }
                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }
                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new WxrImportHttpHandler($executor, $json, $problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.wxr_import',
                static function (ContainerInterface $c): WxrImportRouteRegistrar {
                    $handler = $c->get(WxrImportHttpHandler::class);

                    if (!$handler instanceof WxrImportHttpHandler) {
                        throw new LogicException('WXR import handler service is invalid.');
                    }

                    return new WxrImportRouteRegistrar($handler);
                },
            );
    }
}
