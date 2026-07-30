<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgConnect;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Config\AesGcmConfigCipher;
use NeNeRecords\OrgConnect\ConnectTokenNotFoundExceptionHandler;
use NeNeRecords\OrgConnect\ConnectTokenRouteRegistrar;
use NeNeRecords\OrgConnect\DeleteConnectTokenHandler;
use NeNeRecords\OrgConnect\DeleteConnectTokenUseCase;
use NeNeRecords\OrgConnect\ListConnectTokensHandler;
use NeNeRecords\OrgConnect\ListConnectTokensUseCase;
use NeNeRecords\OrgConnect\PdoConnectTokenRepository;
use NeNeRecords\OrgConnect\SaveConnectTokenHandler;
use NeNeRecords\OrgConnect\SaveConnectTokenUseCase;
use NeNeRecords\OrgExport\OrgExportRepositoryInterface;
use NeNeRecords\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The four surfaces a stored connect-token must never reach (#1029 acceptance condition).
 *
 * Two of these are behavioural and two are structural, and the difference matters when
 * reading a green run:
 *
 * - **Admin API** and **org export** are behavioural: a real token goes into a real store and
 *   the assertion is about what actually comes out.
 * - **Public bootstrap JSON** and **SSR HTML** are structural — they assert that the render
 *   path does not reference this module at all. A behavioural version would be vacuous today
 *   (those paths read entities and settings, and the token lives in neither), so it would pass
 *   whether or not the wiring were safe. The structural pin instead fails the moment someone
 *   makes the render path aware of connect-tokens, which is the regression worth catching.
 */
final class ConnectTokenLeakPinTest extends TestCase
{
    private const TOKEN = 'eyJhbGciOiJIUzI1NiJ9.payload.signature-abcd';

    /** Pin ①: the admin API returns the hint, never the token or its envelope. */
    public function testAdminApiNeverReturnsTheTokenOrItsEnvelope(): void
    {
        $orgId = new RequestScopedHolder();
        $orgId->set(1);
        $executor = $this->sqliteWithConnectTokenSchema();
        $repository = new PdoConnectTokenRepository($executor, $orgId, new FixedClock());

        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $jsonResponse = new JsonResponseFactory($factory, $factory);
        $cipher = new AesGcmConfigCipher(new TestConfigKeyResolver());

        $application = (new RuntimeApplicationFactory(
            $factory,
            $factory,
            domainExceptionHandlers: [
                new ConnectTokenNotFoundExceptionHandler(new ProblemDetailsResponseFactory($factory, $factory)),
            ],
            routeRegistrars: [new ConnectTokenRouteRegistrar(
                new ListConnectTokensHandler(new ListConnectTokensUseCase($repository), $jsonResponse),
                new SaveConnectTokenHandler(new SaveConnectTokenUseCase($repository, $cipher), $jsonResponse),
                new DeleteConnectTokenHandler(new DeleteConnectTokenUseCase($repository), $jsonResponse),
            )],
        ))->create();

        $install = $application->handle(
            $factory->createServerRequest('PUT', 'https://example.test/api/v1/connect-tokens/contact')
                ->withBody($factory->createStream((string) json_encode(['token' => self::TOKEN]))),
        );
        $list = $application->handle(
            $factory->createServerRequest('GET', 'https://example.test/api/v1/connect-tokens'),
        );

        $row = $executor->fetchOne('SELECT token_ciphertext FROM org_connect_tokens WHERE organization_id = 1');
        self::assertNotNull($row, 'The token must actually be stored, or this pin proves nothing.');
        $envelope = (string) $row['token_ciphertext'];

        foreach ([$install, $list] as $response) {
            $body = (string) $response->getBody();
            self::assertStringNotContainsString(self::TOKEN, $body);
            self::assertStringNotContainsString($envelope, $body);
        }

        self::assertStringContainsString('abcd', (string) $list->getBody(), 'The hint is what the UI is meant to get.');
    }

    /** Pin ②: the tenant export must not learn this table exists. */
    public function testOrgExportNeverEnumeratesConnectTokens(): void
    {
        foreach ((new ReflectionClass(OrgExportRepositoryInterface::class))->getMethods() as $method) {
            self::assertStringNotContainsStringIgnoringCase(
                'connecttoken',
                $method->getName(),
                'The export interface must have no connect-token reader.',
            );
        }

        $payloadBuilder = $this->readSource('src/OrgExport/OrgExportPayloadBuilder.php');
        $exportRepository = $this->readSource('src/OrgExport/PdoOrgExportRepository.php');

        // Anti-vacuous: these files must really be the export, i.e. mention a table that *is*
        // exported. Without this the assertions below would also pass on an empty read.
        self::assertStringContainsString('webhooks', $payloadBuilder);
        self::assertStringContainsString('webhooks', $exportRepository);

        foreach (['org_connect_tokens', 'ConnectToken'] as $needle) {
            self::assertStringNotContainsString($needle, $payloadBuilder);
            self::assertStringNotContainsString($needle, $exportRepository);
        }
    }

    /**
     * Pin ③: nothing that builds the public bootstrap JSON knows about connect-tokens.
     *
     * Do not "upgrade" this to a behavioural test. The public render path reads entities and
     * settings; connect-tokens live in their own table behind their own repository, so
     * seeding a token and asserting it is absent from the bootstrap would pass whether or not
     * the wiring were safe — a green that proves nothing. This form fails the moment the
     * render path gains a reference to the module, which is the regression worth catching.
     */
    public function testPublicBootstrapPayloadHasNoPathToConnectTokens(): void
    {
        $sources = [
            'src/PublicRecord/PublicRecordViewHttpMapper.php',
            'src/PublicRecord/GetPublicRecordViewUseCase.php',
            'src/PreviewToken/GetPreviewRecordViewUseCase.php',
            'src/Setting/ListPublicSettingsUseCase.php',
            'src/Setting/SettingHttpMapper.php',
        ];

        foreach ($sources as $source) {
            $code = $this->readSource($source);

            self::assertStringContainsString('class', $code, $source . ' was read as empty.');
            self::assertStringNotContainsString('ConnectToken', $code, $source . ' must not reach connect-tokens.');
            self::assertStringNotContainsString('org_connect_tokens', $code);
        }
    }

    /**
     * Pin ④: nor does anything that renders HTML — SSR templates or the admin SPA shell.
     *
     * Structural for the same reason as pin ③: the renderers read entities and settings, so a
     * behavioural version would be vacuous today and would stay green through an unsafe
     * rewiring. Keep it structural, and add new render entry points to the globs below.
     */
    public function testRenderedHtmlHasNoPathToConnectTokens(): void
    {
        $root = dirname(__DIR__, 2);
        $files = [$root . '/src/Http/SpaShellFallback.php'];

        foreach ((array) glob($root . '/templates/public/*.php') as $template) {
            if (is_string($template)) {
                $files[] = $template;
            }
        }

        foreach ((array) glob($root . '/src/PublicRecord/Render*.php') as $renderer) {
            if (is_string($renderer)) {
                $files[] = $renderer;
            }
        }

        // Anti-vacuous: the globs must actually have matched the render layer.
        self::assertGreaterThan(5, count($files), 'The HTML render surface was not found — the globs are stale.');

        foreach ($files as $file) {
            $code = (string) file_get_contents($file);

            self::assertNotSame('', $code, $file . ' was read as empty.');
            self::assertStringNotContainsString('ConnectToken', $code, $file . ' must not reach connect-tokens.');
            self::assertStringNotContainsString('org_connect_tokens', $code);
        }
    }

    private function readSource(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        self::assertFileExists($path, $relativePath . ' has moved — this pin needs repointing, not deleting.');

        return (string) file_get_contents($path);
    }

    private function sqliteWithConnectTokenSchema(): PdoDatabaseQueryExecutor
    {
        $executor = new PdoDatabaseQueryExecutor(new PdoConnectionFactory(new DatabaseConfig(
            null,
            'test',
            'sqlite',
            'localhost',
            1,
            ':memory:',
            'nene-records-test',
            '',
            'utf8',
        )));

        $raw = trim((string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/org_connect_tokens.sql'));

        foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $executor->execute($statement);
            }
        }

        return $executor;
    }
}
