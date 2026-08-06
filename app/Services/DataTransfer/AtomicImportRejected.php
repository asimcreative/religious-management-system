<?php

namespace App\Services\DataTransfer;

use RuntimeException;

/**
 * Unwinds an all-or-nothing import that hit an invalid row.
 *
 * Thrown from inside the transaction purely to roll it back; it never reaches
 * the user, who is shown the counted failures instead.
 */
class AtomicImportRejected extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The import was cancelled because the file contains invalid rows.');
    }
}
