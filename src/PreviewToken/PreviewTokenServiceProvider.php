<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\BoolField\BoolFieldRepositoryInterface;
use NeNeRecords\DateTimeField\DateTimeFieldRepositoryInterface;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\EntityRelation\EntityRelationRepositoryInterface;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\EnumField\EnumFieldRepositoryInterface;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\IntField\IntFieldRepositoryInterface;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use NeNeRecords\TextField\TextFieldRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class PreviewTokenServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                EntityPreviewTokenRepositoryInterface::class,
                static function (ContainerInterface $container): EntityPreviewTokenRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoEntityPreviewTokenRepository($query);
                },
            )
            ->set(
                GeneratePreviewTokenUseCaseInterface::class,
                static function (ContainerInterface $container): GeneratePreviewTokenUseCaseInterface {
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $previewTokens = $container->get(EntityPreviewTokenRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$previewTokens instanceof EntityPreviewTokenRepositoryInterface) {
                        throw new LogicException('EntityPreviewToken repository service is invalid.');
                    }

                    return new GeneratePreviewTokenUseCase($entities, $previewTokens);
                },
            )
            ->set(
                RevokePreviewTokenUseCaseInterface::class,
                static function (ContainerInterface $container): RevokePreviewTokenUseCaseInterface {
                    $entities = $container->get(EntityRepositoryInterface::class);
                    $previewTokens = $container->get(EntityPreviewTokenRepositoryInterface::class);

                    if (!$entities instanceof EntityRepositoryInterface) {
                        throw new LogicException('Entity repository service is invalid.');
                    }

                    if (!$previewTokens instanceof EntityPreviewTokenRepositoryInterface) {
                        throw new LogicException('EntityPreviewToken repository service is invalid.');
                    }

                    return new RevokePreviewTokenUseCase($entities, $previewTokens);
                },
            )
            ->set(
                GetPreviewRecordViewUseCaseInterface::class,
                static function (ContainerInterface $container): GetPreviewRecordViewUseCaseInterface {
                    $previewTokens = $container->get(EntityPreviewTokenRepositoryInterface::class);
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

                    if (!$previewTokens instanceof EntityPreviewTokenRepositoryInterface) {
                        throw new LogicException('EntityPreviewToken repository service is invalid.');
                    }

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

                    return new GetPreviewRecordViewUseCase(
                        $previewTokens,
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
                GeneratePreviewTokenHandler::class,
                static function (ContainerInterface $container): GeneratePreviewTokenHandler {
                    $useCase = $container->get(GeneratePreviewTokenUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GeneratePreviewTokenUseCaseInterface) {
                        throw new LogicException('GeneratePreviewToken use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GeneratePreviewTokenHandler($useCase, $response);
                },
            )
            ->set(
                RevokePreviewTokenHandler::class,
                static function (ContainerInterface $container): RevokePreviewTokenHandler {
                    $useCase = $container->get(RevokePreviewTokenUseCaseInterface::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof RevokePreviewTokenUseCaseInterface) {
                        throw new LogicException('RevokePreviewToken use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    return new RevokePreviewTokenHandler($useCase, $responseFactory);
                },
            )
            ->set(
                GetPreviewRecordViewHandler::class,
                static function (ContainerInterface $container): GetPreviewRecordViewHandler {
                    $useCase = $container->get(GetPreviewRecordViewUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetPreviewRecordViewUseCaseInterface) {
                        throw new LogicException('GetPreviewRecordView use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetPreviewRecordViewHandler($useCase, $response);
                },
            )
            ->set(
                PreviewTokenNotFoundExceptionHandler::class,
                static function (ContainerInterface $container): PreviewTokenNotFoundExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new PreviewTokenNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                'nene-records.route_registrar.preview_token',
                static function (ContainerInterface $container): PreviewTokenRouteRegistrar {
                    $generateHandler = $container->get(GeneratePreviewTokenHandler::class);
                    $revokeHandler = $container->get(RevokePreviewTokenHandler::class);
                    $getHandler = $container->get(GetPreviewRecordViewHandler::class);

                    if (!$generateHandler instanceof GeneratePreviewTokenHandler) {
                        throw new LogicException('GeneratePreviewToken handler service is invalid.');
                    }

                    if (!$revokeHandler instanceof RevokePreviewTokenHandler) {
                        throw new LogicException('RevokePreviewToken handler service is invalid.');
                    }

                    if (!$getHandler instanceof GetPreviewRecordViewHandler) {
                        throw new LogicException('GetPreviewRecordView handler service is invalid.');
                    }

                    return new PreviewTokenRouteRegistrar($generateHandler, $revokeHandler, $getHandler);
                },
            );
    }
}
