<?php

namespace Vendimia\Database;

/**
 * Support class for mark a value which has already been escaped and quoted.
 */
class DatabaseReadyValue
{
    public function __construct(private $value)
    {

    }

    public function getValue()
    {
        return $this->value;
    }
}