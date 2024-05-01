<?php
namespace Vendimia\Database;

use Exception;
use Throwable;

class DatabaseException extends Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,

        /** Extra information for this exception */
        private array $extra = [],
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
