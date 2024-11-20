<?php

require __DIR__ . '/../vendor/autoload.php';

Vendimia\Database\Setup::init(
    new Vendimia\Database\Driver\Mysql\Connector(
        hostname: 'localhost',
        username: 'root',       // FIXME: Must be obtained via $_ENV
        password: 'pikachu',    // FIXME: Must be obtained via $_ENV
        database: 'mysql',      // FIXME: Must be obtained via $_ENV
    ),
);
