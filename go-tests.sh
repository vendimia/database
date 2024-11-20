#!/bin/bash

for driver in sqlite mysql; do
    echo "== $driver"
    phpunit --bootstrap=tests/bootstrap-$driver.php tests/
done;
