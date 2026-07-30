<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgConnect;

use NeNeRecords\Config\AesGcmConfigCipher;
use NeNeRecords\Config\ConfigDecryptException;
use NeNeRecords\OrgConnect\ConnectTokenNotFoundException;
use NeNeRecords\OrgConnect\ConnectTokenService;
use NeNeRecords\OrgConnect\DeleteConnectTokenInput;
use NeNeRecords\OrgConnect\DeleteConnectTokenUseCase;
use NeNeRecords\OrgConnect\ListConnectTokensUseCase;
use NeNeRecords\OrgConnect\SaveConnectTokenInput;
use NeNeRecords\OrgConnect\SaveConnectTokenUseCase;
use NeNeRecords\OrgConnect\StoredConnectTokenProvider;
use PHPUnit\Framework\TestCase;

final class ConnectTokenUseCaseTest extends TestCase
{
    private const TOKEN = 'eyJhbGciOiJIUzI1NiJ9.payload.signature-abcd';

    public function testSaveStoresCiphertextAndNeverThePlaintext(): void
    {
        $repository = new InMemoryConnectTokenRepository();
        $useCase = new SaveConnectTokenUseCase($repository, $this->cipher());

        $output = $useCase->execute(new SaveConnectTokenInput(ConnectTokenService::Contact, self::TOKEN));

        $stored = $repository->storedEnvelope(ConnectTokenService::Contact);

        self::assertTrue($output->created);
        self::assertNotNull($stored);
        self::assertStringNotContainsString(self::TOKEN, $stored);
        self::assertStringNotContainsString(self::TOKEN, (string) base64_decode($stored, true));
    }

    public function testHintKeepsOnlyTrailingCharacters(): void
    {
        $repository = new InMemoryConnectTokenRepository();

        $output = (new SaveConnectTokenUseCase($repository, $this->cipher()))
            ->execute(new SaveConnectTokenInput(ConnectTokenService::Contact, self::TOKEN));

        self::assertSame('abcd', $output->summary->hint);
        self::assertStringEndsWith($output->summary->hint, self::TOKEN);
        self::assertLessThanOrEqual(8, strlen($output->summary->hint), 'The hint column caps at 8 characters.');
    }

    public function testSavingTwiceReplacesInPlaceAndReportsNotCreated(): void
    {
        $repository = new InMemoryConnectTokenRepository();
        $useCase = new SaveConnectTokenUseCase($repository, $this->cipher());

        $useCase->execute(new SaveConnectTokenInput(ConnectTokenService::Contact, self::TOKEN));
        $second = $useCase->execute(new SaveConnectTokenInput(ConnectTokenService::Contact, 'second-token-value-wxyz'));

        self::assertFalse($second->created);
        self::assertSame('wxyz', $second->summary->hint);
        self::assertCount(1, (new ListConnectTokensUseCase($repository))->execute()->items);
    }

    public function testProviderReturnsThePlaintextBack(): void
    {
        $repository = new InMemoryConnectTokenRepository();
        $cipher = $this->cipher();
        (new SaveConnectTokenUseCase($repository, $cipher))
            ->execute(new SaveConnectTokenInput(ConnectTokenService::Contact, self::TOKEN));

        $provider = new StoredConnectTokenProvider($repository, $cipher);

        self::assertSame(self::TOKEN, $provider->secretFor(ConnectTokenService::Contact));
    }

    public function testProviderReturnsNullWhenNothingIsInstalled(): void
    {
        $provider = new StoredConnectTokenProvider(new InMemoryConnectTokenRepository(), $this->cipher());

        self::assertNull($provider->secretFor(ConnectTokenService::Contact));
    }

    public function testProviderSurfacesARotatedKeyInsteadOfReportingNoToken(): void
    {
        $repository = new InMemoryConnectTokenRepository();
        (new SaveConnectTokenUseCase($repository, $this->cipher()))
            ->execute(new SaveConnectTokenInput(ConnectTokenService::Contact, self::TOKEN));

        // "Installed but unreadable" and "not installed" need different operator actions:
        // re-paste vs. install. Collapsing them into null would hide a broken deployment.
        $rotated = new StoredConnectTokenProvider(
            $repository,
            new AesGcmConfigCipher(new TestConfigKeyResolver(str_repeat("\x02", 32))),
        );

        $this->expectException(ConfigDecryptException::class);
        $rotated->secretFor(ConnectTokenService::Contact);
    }

    public function testDeleteRejectsATokenThatIsNotInstalled(): void
    {
        $useCase = new DeleteConnectTokenUseCase(new InMemoryConnectTokenRepository());

        $this->expectException(ConnectTokenNotFoundException::class);
        $useCase->execute(new DeleteConnectTokenInput(ConnectTokenService::Contact));
    }

    public function testDeleteRemovesTheStoredEnvelope(): void
    {
        $repository = new InMemoryConnectTokenRepository();
        (new SaveConnectTokenUseCase($repository, $this->cipher()))
            ->execute(new SaveConnectTokenInput(ConnectTokenService::Contact, self::TOKEN));

        (new DeleteConnectTokenUseCase($repository))->execute(new DeleteConnectTokenInput(ConnectTokenService::Contact));

        self::assertNull($repository->storedEnvelope(ConnectTokenService::Contact));
        self::assertSame([], (new ListConnectTokensUseCase($repository))->execute()->items);
    }

    private function cipher(): AesGcmConfigCipher
    {
        return new AesGcmConfigCipher(new TestConfigKeyResolver());
    }
}
