<?php

namespace Vendimia\Database;

use Exception;
use Throwable;

/**
 * Exception for database errors. Include an 'extra' field used for SQL query.
 *
 * This is the same as Vendimia\Exception\VendimiaException found in package
 * vendimia/core, but repeated here just for decoupling purposes.
 */
class DatabaseException extends Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,

        /** Extra information for this exception */
        protected array $extra = [],
    )
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns extra information provided by the error
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
