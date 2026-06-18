<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

final readonly class CreateThemeUseCase implements CreateThemeUseCaseInterface
{
    public function __construct(
        private ThemeRepositoryInterface $repository,
    ) {
    }

    public function execute(CreateThemeInput $input): CreateThemeOutput
    {
        ThemeManifestValidator::validate($input->manifest);

        $key = (string) $input->manifest['id'];

        if ($this->repository->existsByKey($key)) {
            throw new ValidationException([
                new ValidationError('id', "A theme with id '{$key}' already exists.", 'conflict'),
            ]);
        }

        $theme = new Theme(
            id: null,
            themeKey: $key,
            name: (string) $input->manifest['name'],
            version: (string) $input->manifest['version'],
            manifest: $input->manifest,
            createdAt: '',
            updatedAt: '',
        );

        $this->repository->save($theme);
        $saved = $this->repository->findByKey($key);

        if ($saved === null) {
            throw new \RuntimeException('Failed to persist theme.');
        }

        return new CreateThemeOutput(theme: $saved);
    }
}
