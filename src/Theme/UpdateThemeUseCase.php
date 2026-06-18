<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

final readonly class UpdateThemeUseCase implements UpdateThemeUseCaseInterface
{
    public function __construct(
        private ThemeRepositoryInterface $repository,
    ) {
    }

    public function execute(UpdateThemeInput $input): UpdateThemeOutput
    {
        $existing = $this->repository->findByKey($input->themeKey);

        if ($existing === null) {
            throw new ThemeNotFoundException($input->themeKey);
        }

        ThemeManifestValidator::validate($input->manifest);

        // The key is immutable on update; renaming means delete + create.
        if ((string) $input->manifest['id'] !== $input->themeKey) {
            throw new ValidationException([
                new ValidationError('id', 'Manifest id must match the theme key in the URL.', 'mismatch'),
            ]);
        }

        $this->repository->update(new Theme(
            id: $existing->id,
            themeKey: $input->themeKey,
            name: (string) $input->manifest['name'],
            version: (string) $input->manifest['version'],
            manifest: $input->manifest,
            createdAt: $existing->createdAt,
            updatedAt: '',
        ));

        $saved = $this->repository->findByKey($input->themeKey);

        if ($saved === null) {
            throw new \RuntimeException('Failed to reload theme after update.');
        }

        return new UpdateThemeOutput(theme: $saved);
    }
}
