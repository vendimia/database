<?php

require __DIR__ . '/../vendor/autoload.php';

Vendimia\Database\Setup::init(
    new Vendimia\Database\Driver\Sqlite\Connector(
        filename: '/tmp/database-test.sqlite',
    ),
);
