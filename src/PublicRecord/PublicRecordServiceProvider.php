<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use LogicException;
use Nene2\Config\AppConfig;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\View\HtmlResponseFactory;
use Nene2\View\NativePhpViewRenderer;
use NeNeRecords\BoolField\BoolFieldRepositoryInterface;
use NeNeRecords\DateTimeField\DateTimeFieldRepositoryInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityRelation\EntityRelationRepositoryInterface;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\EnumField\EnumFieldRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\Http\PublicPermalinkRendererInterface;
use NeNeRecords\Http\RuntimeServiceProvider;
use NeNeRecords\IntField\IntFieldRepositoryInterface;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use NeNeRecords\TextField\TextFieldRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class PublicRecordServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                NativePhpViewRenderer::class,
                static function (ContainerInterface $container): NativePhpViewRenderer {
                    $projectRoot = $container->get(RuntimeServiceProvider::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new NativePhpViewRenderer($projectRoot . '/templates');
                },
            )
            ->set(
                HtmlResponseFactory::class,
                static function (ContainerInterface $container): HtmlResponseFactory {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);
                    $renderer = $container->get(NativePhpViewRenderer::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    if (!$renderer instanceof NativePhpViewRenderer) {
                        throw new LogicException('Native PHP view renderer service is invalid.');
                    }

                    return new HtmlResponseFactory($responseFactory, $streamFactory, $renderer);
                },
            )
            ->set(
                PublicRecordHierarchyBuilder::class,
                static function (ContainerInterface $container): PublicRecordHierarchyBuilder {
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $textFields = $container->get(TextFieldRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    return new PublicRecordHierarchyBuilder($entities, $textFields);
                },
            )
            ->set(
                GetPublicRecordViewUseCaseInterface::class,
                static function (ContainerInterface $container): GetPublicRecordViewUseCaseInterface {
                    $entityTypes = $container->get(EntityTypeRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $fieldDefs = $container->get(FieldDefRepositoryInterface::class);
                    $textFields = $container->get(TextFieldRepositoryInterface::class);
                    $intFields = $container->get(IntFieldRepositoryInterface::class);
                    $enumFields = $container->get(EnumFieldRepositoryInterface::class);
                    $boolFields = $container->get(BoolFieldRepositoryInterface::class);
                    $dateTimeFields = $container->get(DateTimeFieldRepositoryInterface::class);
                    $entityRelations = $container->get(EntityRelationRepositoryInterface::class);
                    $publicSettings = $container->get(ListPublicSettingsUseCaseInterface::class);
                    $hierarchyBuilder = $container->get(PublicRecordHierarchyBuilder::class);

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$hierarchyBuilder instanceof PublicRecordHierarchyBuilder) {
                        throw new LogicException('PublicRecordHierarchy builder service is invalid.');
                    }

                    if (!$fieldDefs instanceof FieldDefRepositoryInterface) {
                        throw new LogicException('Field def repository service is invalid.');
                    }

                    if (!$textFields instanceof TextFieldRepositoryInterface) {
                        throw new LogicException('Text field repository service is invalid.');
                    }

                    if (!$intFields instanceof IntFieldRepositoryInterface) {
                        throw new LogicException('Int field repository service is invalid.');
                    }

                    if (!$enumFields instanceof EnumFieldRepositoryInterface) {
                        throw new LogicException('Enum field repository service is invalid.');
                    }

                    if (!$boolFields instanceof BoolFieldRepositoryInterface) {
                        throw new LogicException('Bool field repository service is invalid.');
                    }

                    if (!$dateTimeFields instanceof DateTimeFieldRepositoryInterface) {
                        throw new LogicException('DateTime field repository service is invalid.');
                    }

                    if (!$entityRelations instanceof EntityRelationRepositoryInterface) {
                        throw new LogicException('Entity relation repository service is invalid.');
                    }

                    if (!$publicSettings instanceof ListPublicSettingsUseCaseInterface) {
                        throw new LogicException('ListPublicSettings use case service is invalid.');
                    }

                    return new GetPublicRecordViewUseCase(
                        $entityTypes,
                        $entities,
                        $fieldDefs,
                        $textFields,
                        $intFields,
                        $enumFields,
                        $boolFields,
                        $dateTimeFields,
                        $entityRelations,
                        $publicSettings,
                        $hierarchyBuilder,
                    );
                },
            )
            ->set(
                GetPublicRecordViewHandler::class,
                static function (ContainerInterface $container): GetPublicRecordViewHandler {
                    $useCase = $container->get(GetPublicRecordViewUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof GetPublicRecordViewUseCaseInterface) {
                        throw new LogicException('GetPublicRecordView use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new GetPublicRecordViewHandler($useCase, $response, $responseFactory);
                },
            )
            ->set(
                GetPublicRecordHierarchyHandler::class,
                static function (ContainerInterface $container): GetPublicRecordHierarchyHandler {
                    $builder = $container->get(PublicRecordHierarchyBuilder::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$builder instanceof PublicRecordHierarchyBuilder) {
                        throw new LogicException('PublicRecordHierarchy builder service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetPublicRecordHierarchyHandler($builder, $response);
                },
            )
            ->set(
                RenderPublicRecordViewHandler::class,
                static function (ContainerInterface $container): RenderPublicRecordViewHandler {
                    $useCase = $container->get(GetPublicRecordViewUseCaseInterface::class);
                    $publicSettings = $container->get(ListPublicSettingsUseCaseInterface::class);
                    $html = $container->get(HtmlResponseFactory::class);
                    $config = $container->get(AppConfig::class);
                    $projectRoot = $container->get(RuntimeServiceProvider::PROJECT_ROOT);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof GetPublicRecordViewUseCaseInterface) {
                        throw new LogicException('GetPublicRecordView use case service is invalid.');
                    }

                    if (!$publicSettings instanceof ListPublicSettingsUseCaseInterface) {
                        throw new LogicException('ListPublicSettings use case service is invalid.');
                    }

                    if (!$html instanceof HtmlResponseFactory) {
                        throw new LogicException('HTML response factory service is invalid.');
                    }

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    if (!is_string($projectRoot)) {
                        throw new LogicException('Project root service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new RenderPublicRecordViewHandler(
                        $useCase,
                        $publicSettings,
                        $html,
                        $config,
                        $projectRoot,
                        $responseFactory,
                        new PublicHtmlSanitizer(),
                        \NeNeRecords\Http\BasePath::fromEnv(),
                    );
                },
            )
            ->set(
                RenderCustomPermalinkHandler::class,
                static function (ContainerInterface $container): RenderCustomPermalinkHandler {
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $entityTypes = $container->get(EntityTypeRepositoryInterface::class);
                    $viewRenderer = $container->get(RenderPublicRecordViewHandler::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$viewRenderer instanceof RenderPublicRecordViewHandler) {
                        throw new LogicException('RenderPublicRecordView handler service is invalid.');
                    }

                    return new RenderCustomPermalinkHandler($entities, $entityTypes, $viewRenderer);
                },
            )
            ->set(
                PublicPermalinkRendererInterface::class,
                static function (ContainerInterface $container): PublicPermalinkRendererInterface {
                    $renderer = $container->get(RenderCustomPermalinkHandler::class);

                    if (!$renderer instanceof RenderCustomPermalinkHandler) {
                        throw new LogicException('RenderCustomPermalink handler service is invalid.');
                    }

                    return $renderer;
                },
            )
            ->set(
                RenderPublicPermalinkHandler::class,
                static function (ContainerInterface $container): RenderPublicPermalinkHandler {
                    $entityTypes = $container->get(EntityTypeRepositoryInterface::class);
                    $viewRenderer = $container->get(RenderPublicRecordViewHandler::class);
                    $customPermalink = $container->get(PublicPermalinkRendererInterface::class);

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$viewRenderer instanceof RenderPublicRecordViewHandler) {
                        throw new LogicException('RenderPublicRecordView handler service is invalid.');
                    }

                    if (!$customPermalink instanceof PublicPermalinkRendererInterface) {
                        throw new LogicException('Public permalink renderer service is invalid.');
                    }

                    return new RenderPublicPermalinkHandler($entityTypes, $viewRenderer, $customPermalink);
                },
            )
            ->set(
                PublicEntityTypeNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): PublicEntityTypeNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new PublicEntityTypeNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                PublicRecordNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): PublicRecordNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new PublicRecordNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                GenerateSitemapUseCaseInterface::class,
                static function (ContainerInterface $container): GenerateSitemapUseCaseInterface {
                    $entityTypes = $container->get(EntityTypeRepositoryInterface::class);
                    $entities = $container->get(EntityRepositoryInterface::class);

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    return new GenerateSitemapUseCase($entityTypes, $entities);
                },
            )
            ->set(
                RenderSitemapHandler::class,
                static function (ContainerInterface $container): RenderSitemapHandler {
                    $useCase = $container->get(GenerateSitemapUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);

                    if (!$useCase instanceof GenerateSitemapUseCaseInterface) {
                        throw new LogicException('GenerateSitemap use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new RenderSitemapHandler(
                        $useCase,
                        $responseFactory,
                        $streamFactory,
                        null,
                        \NeNeRecords\Http\BasePath::fromEnv(),
                    );
                },
            )
            ->set(
                RenderRobotsHandler::class,
                static function (ContainerInterface $container): RenderRobotsHandler {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new RenderRobotsHandler(
                        $responseFactory,
                        $streamFactory,
                        \NeNeRecords\Http\BasePath::fromEnv(),
                    );
                },
            )
            ->set(
                'nene-records.route_registrar.public_record',
                static function (ContainerInterface $container): PublicRecordRouteRegistrar {
                    $getHandler = $container->get(GetPublicRecordViewHandler::class);
                    $hierarchyHandler = $container->get(GetPublicRecordHierarchyHandler::class);
                    $renderHandler = $container->get(RenderPublicRecordViewHandler::class);
                    $permalinkHandler = $container->get(RenderPublicPermalinkHandler::class);
                    $sitemapHandler = $container->get(RenderSitemapHandler::class);
                    $robotsHandler = $container->get(RenderRobotsHandler::class);

                    if (!$getHandler instanceof GetPublicRecordViewHandler) {
                        throw new LogicException('GetPublicRecordView handler service is invalid.');
                    }

                    if (!$hierarchyHandler instanceof GetPublicRecordHierarchyHandler) {
                        throw new LogicException('GetPublicRecordHierarchy handler service is invalid.');
                    }

                    if (!$renderHandler instanceof RenderPublicRecordViewHandler) {
                        throw new LogicException('RenderPublicRecordView handler service is invalid.');
                    }

                    if (!$permalinkHandler instanceof RenderPublicPermalinkHandler) {
                        throw new LogicException('RenderPublicPermalink handler service is invalid.');
                    }

                    if (!$sitemapHandler instanceof RenderSitemapHandler) {
                        throw new LogicException('RenderSitemap handler service is invalid.');
                    }

                    if (!$robotsHandler instanceof RenderRobotsHandler) {
                        throw new LogicException('RenderRobots handler service is invalid.');
                    }

                    return new PublicRecordRouteRegistrar(
                        $getHandler,
                        $hierarchyHandler,
                        $renderHandler,
                        $permalinkHandler,
                        $sitemapHandler,
                        $robotsHandler,
                    );
                },
            );
    }
}
