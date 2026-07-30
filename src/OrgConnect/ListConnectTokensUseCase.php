<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

final readonly class ListConnectTokensUseCase implements ListConnectTokensUseCaseInterface
{
    public function __construct(
        private ConnectTokenRepositoryInterface $tokens,
    ) {
    }

    public function execute(): ListConnectTokensOutput
    {
        return new ListConnectTokensOutput(
            items: $this->tokens->findAllSummaries(),
        );
    }
}
