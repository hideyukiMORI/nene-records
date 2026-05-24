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

                    if (!$entityTypes instanceof EntityTypeRepositoryInterface) {
                        throw new LogicException('Entity type repository service is invalid.');
                    }

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
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
                    );
                },
            )
            ->set(
                GetPublicRecordViewHandler::class,
                static function (ContainerInterface $container): GetPublicRecordViewHandler {
                    $useCase = $container->get(GetPublicRecordViewUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetPublicRecordViewUseCaseInterface) {
                        throw new LogicException('GetPublicRecordView use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetPublicRecordViewHandler($useCase, $response);
                },
            )
            ->set(
                RenderPublicRecordViewHandler::class,
                static function (ContainerInterface $container): RenderPublicRecordViewHandler {
                    $useCase = $container->get(GetPublicRecordViewUseCaseInterface::class);
                    $publicSettings = $container->get(ListPublicSettingsUseCaseInterface::class);
                    $html = $container->get(HtmlResponseFactory::class);
                    $config = $container->get(AppConfig::class);

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

                    return new RenderPublicRecordViewHandler($useCase, $publicSettings, $html, $config);
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
                'nene-records.route_registrar.public_record',
                static function (ContainerInterface $container): PublicRecordRouteRegistrar {
                    $getHandler = $container->get(GetPublicRecordViewHandler::class);
                    $renderHandler = $container->get(RenderPublicRecordViewHandler::class);

                    if (!$getHandler instanceof GetPublicRecordViewHandler) {
                        throw new LogicException('GetPublicRecordView handler service is invalid.');
                    }

                    if (!$renderHandler instanceof RenderPublicRecordViewHandler) {
                        throw new LogicException('RenderPublicRecordView handler service is invalid.');
                    }

                    return new PublicRecordRouteRegistrar($getHandler, $renderHandler);
                },
            );
    }
}
