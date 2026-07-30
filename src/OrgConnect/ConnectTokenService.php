<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

/**
 * The products records may hold a connect-token for.
 *
 * An allow-list, not free text: the service is part of the table's unique key and of the
 * admin URL, so accepting arbitrary strings would let an operator create rows nothing ever
 * reads, and would make "which integrations exist" unanswerable from the code.
 *
 * records never mints these tokens — the named product issues one and the operator pastes
 * it here (#1029, epic #1001).
 */
enum ConnectTokenService: string
{
    case Contact = 'contact';
}
