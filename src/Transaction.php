<?php
namespace Vendimia\Database;

use Throwable;

/**
 * Transaction manager
 */
class Transaction
{
    /**
     * Executes a callback inside a transaction.
     *
     * If an exception is thrown or an error occured inside the callable, the
     * transaction will automatically be rolled back.
     */
    public static function go(Callable $callable): mixed
    {
        self::start();
        try {
            $result = $callable();
        } catch (Throwable $e) {
            self::rollback();
            throw($e);
        }

        self::commit();
        return $result;
    }

    public static function start()
    {
        Setup::$connector->startTransaction();
    }

    public static function commit()
    {
        Setup::$connector->commitTransaction();
    }

    public static function rollback()
    {
        Setup::$connector->rollbackTransaction();
    }
}