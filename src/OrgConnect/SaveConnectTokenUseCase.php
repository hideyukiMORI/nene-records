<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use NeNeRecords\Config\ConfigCipherInterface;

final readonly class SaveConnectTokenUseCase implements SaveConnectTokenUseCaseInterface
{
    /**
     * How many trailing characters the admin UI gets to see. Four is enough to tell two
     * pasted tokens apart and far short of useful: the column itself caps at 8.
     */
    private const HINT_LENGTH = 4;

    public function __construct(
        private ConnectTokenRepositoryInterface $tokens,
        private ConfigCipherInterface $cipher,
    ) {
    }

    public function execute(SaveConnectTokenInput $input): SaveConnectTokenOutput
    {
        $created = $this->tokens->findSummary($input->service) === null;

        // Encrypt before writing, and let a missing key throw out of here: a fail-closed
        // refusal is the point, so there is deliberately no branch that stores the plaintext.
        $envelope = $this->cipher->encrypt($input->token);

        $summary = $this->tokens->save(
            $input->service,
            $envelope,
            substr($input->token, -self::HINT_LENGTH),
        );

        return new SaveConnectTokenOutput($summary, $created);
    }
}
