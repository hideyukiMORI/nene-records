<?php

declare(strict_types=1);

/**
 * First-run onboarding: create the initial organization (auto-seeding its
 * default content types) and the admin user, turning a freshly-migrated
 * database into a usable instance. Idempotent — safe to run on every deploy.
 *
 * Run after migrations (the `app:install` composer script chains both):
 *   NENE_INSTALL_ADMIN_EMAIL=admin@example.com \
 *   NENE_INSTALL_ADMIN_PASSWORD='change-me' \
 *     docker compose exec -T app composer app:install
 *
 * Environment:
 *   NENE_INSTALL_ADMIN_EMAIL     (required) initial admin login email
 *   NENE_INSTALL_ADMIN_PASSWORD  (required) initial admin password
 *   NENE_INSTALL_ORG_NAME        (default "NeNe Records")
 *   NENE_INSTALL_ORG_SLUG        (default "default")
 */

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\ApplicationServiceProvider;
use NeNeRecords\Http\RuntimeContainerFactory;
use NeNeRecords\Install\InstallApplication;
use NeNeRecords\Install\InstallConfig;
use NeNeRecords\Organization\CreateOrganizationUseCaseInterface;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\Setting\UpdateSettingUseCaseInterface;
use NeNeRecords\User\CreateUserUseCaseInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$email = getenv('NENE_INSTALL_ADMIN_EMAIL');
$password = getenv('NENE_INSTALL_ADMIN_PASSWORD');

if (!is_string($email) || $email === '' || !is_string($password) || $password === '') {
    fwrite(STDERR, "ERROR: NENE_INSTALL_ADMIN_EMAIL and NENE_INSTALL_ADMIN_PASSWORD are required.\n");
    exit(1);
}

$orgNameEnv = getenv('NENE_INSTALL_ORG_NAME');
$orgSlugEnv = getenv('NENE_INSTALL_ORG_SLUG');
$orgName = is_string($orgNameEnv) && $orgNameEnv !== '' ? $orgNameEnv : 'NeNe Records';
$orgSlug = is_string($orgSlugEnv) && $orgSlugEnv !== '' ? $orgSlugEnv : 'default';

$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

$createOrganization = $container->get(CreateOrganizationUseCaseInterface::class);
$organizations = $container->get(OrganizationRepositoryInterface::class);
$createUser = $container->get(CreateUserUseCaseInterface::class);
$orgHolder = $container->get(ApplicationServiceProvider::ORG_ID_HOLDER);
$updateSetting = $container->get(UpdateSettingUseCaseInterface::class);

if (
    !$createOrganization instanceof CreateOrganizationUseCaseInterface
    || !$organizations instanceof OrganizationRepositoryInterface
    || !$createUser instanceof CreateUserUseCaseInterface
    || !$orgHolder instanceof RequestScopedHolder
    || !$updateSetting instanceof UpdateSettingUseCaseInterface
) {
    fwrite(STDERR, "ERROR: the application container is misconfigured.\n");
    exit(1);
}

/** @var RequestScopedHolder<int> $orgHolder */
$result = (new InstallApplication($createOrganization, $organizations, $createUser, $orgHolder, $updateSetting))
    ->install(new InstallConfig($orgName, $orgSlug, $email, $password));

printf(
    "✓ Organization '%s' (#%d) %s\n",
    $result->organizationSlug,
    $result->organizationId,
    $result->organizationCreated ? 'created' : 'already existed',
);
printf(
    "✓ Admin '%s' %s\n",
    $result->adminEmail,
    $result->adminCreated ? 'created' : 'already existed',
);
echo "\nNext steps:\n";
echo "  1. Build the frontend:  npm ci && npm run build --prefix frontend\n";
echo "  2. Point your public domain at this app (single-origin); see docs/deployment.md.\n";

exit(0);
