<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\TextField;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\CreateEntityHandler;
use NeNeRecords\Entity\CreateEntityUseCase;
use NeNeRecords\Entity\DeleteEntityHandler;
use NeNeRecords\Entity\DeleteEntityUseCase;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityRouteRegistrar;
use NeNeRecords\Entity\GetEntityByIdHandler;
use NeNeRecords\Entity\GetEntityByIdUseCase;
use NeNeRecords\Entity\ListEntitiesHandler;
use NeNeRecords\Entity\ListEntitiesUseCase;
use NeNeRecords\Entity\UpdateEntityHandler;
use NeNeRecords\Entity\UpdateEntityUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\TextField\CreateTextFieldHandler;
use NeNeRecords\TextField\CreateTextFieldUseCase;
use NeNeRecords\TextField\DeleteTextFieldHandler;
use NeNeRecords\TextField\DeleteTextFieldUseCase;
use NeNeRecords\TextField\GetTextFieldByIdHandler;
use NeNeRecords\TextField\GetTextFieldByIdUseCase;
use NeNeRecords\TextField\ListTextFieldsHandler;
use NeNeRecords\TextField\ListTextFieldsUseCase;
use NeNeRecords\TextField\TextFieldNotFoundExceptionHandler;
use NeNeRecords\TextField\TextFieldRouteRegistrar;
use NeNeRecords\TextField\UpdateTextFieldHandler;
use NeNeRecords\TextField\UpdateTextFieldUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TextFieldHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryEntityRepository $entities;
    private InMemoryTextFieldRepository $textFields;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entityTypes = new InMemoryEntityTypeRepository();
        $this->entities = new InMemoryEntityRepository();
        $this->textFields = new InMemoryTextFieldRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $entityRegistrar = new EntityRouteRegistrar(
            new GetEntityByIdHandler(new GetEntityByIdUseCase($this->entities), $jsonResponse),
            new CreateEntityHandler(new CreateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new UpdateEntityHandler(new UpdateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new DeleteEntityHandler(new DeleteEntityUseCase($this->entities), $this->factory),
            new ListEntitiesHandler(new ListEntitiesUseCase($this->entities), $jsonResponse),
        );

        $textFieldRegistrar = new TextFieldRouteRegistrar(
            new ListTextFieldsHandler(new ListTextFieldsUseCase($this->textFields), $jsonResponse),
            new GetTextFieldByIdHandler(new GetTextFieldByIdUseCase($this->textFields), $jsonResponse),
            new CreateTextFieldHandler(new CreateTextFieldUseCase($this->textFields, $this->entities), $jsonResponse),
            new UpdateTextFieldHandler(new UpdateTextFieldUseCase($this->textFields), $jsonResponse),
            new DeleteTextFieldHandler(new DeleteTextFieldUseCase($this->textFields), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityTypeNotFoundExceptionHandler($problemDetails),
                new EntityNotFoundExceptionHandler($problemDetails),
                new TextFieldNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$entityRegistrar, $textFieldRegistrar],
        ))->create();
    }

    public function testPostTextFieldCreatesFieldAndReturns201WithLocation(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        self::assertSame(201, $entityResponse->getStatusCode());
        $createdEntity = $this->decodeJson($entityResponse);
        $entityId = (int) $createdEntity['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'title',
            'value' => 'Hello Field',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyTf),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/text-fields/', $response->getHeaderLine('Location'));
        self::assertSame($entityId, $payload['entity_id']);
        self::assertSame('title', $payload['field_key']);
        self::assertSame('Hello Field', $payload['value']);
    }

    public function testAfterDeleteGetTextFieldReturns404(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Item', slug: 'item'));
        $bodyEntity = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));

        $entityResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($bodyEntity),
        );
        $entityId = (int) $this->decodeJson($entityResponse)['id'];

        $bodyTf = $this->factory->createStream(json_encode([
            'entity_id' => $entityId,
            'field_key' => 'body',
            'value' => 'Text',
        ], JSON_THROW_ON_ERROR));

        $createTf = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/text-fields')->withBody($bodyTf),
        );
        self::assertSame(201, $createTf->getStatusCode());
        $id = (int) $this->decodeJson($createTf)['id'];

        $getBefore = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/text-fields/{$id}"),
        );
        self::assertSame(200, $getBefore->getStatusCode());

        $del = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/text-fields/{$id}"),
        );
        self::assertSame(204, $del->getStatusCode());

        $getAfter = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/text-fields/{$id}"),
        );
        $payload = $this->decodeJson($getAfter);

        self::assertSame(404, $getAfter->getStatusCode());
        self::assertStringEndsWith('not-found', (string) $payload['type']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);

        return $payload;
    }
}
