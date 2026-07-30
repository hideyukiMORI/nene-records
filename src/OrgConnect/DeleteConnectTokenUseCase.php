<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

final readonly class DeleteConnectTokenUseCase implements DeleteConnectTokenUseCaseInterface
{
    public function __construct(
        private ConnectTokenRepositoryInterface $tokens,
    ) {
    }

    public function execute(DeleteConnectTokenInput $input): void
    {
        if ($this->tokens->findSummary($input->service) === null) {
            throw new ConnectTokenNotFoundException($input->service->value);
        }

        $this->tokens->delete($input->service);
    }
}
