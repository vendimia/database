#!/bin/bash

for driver in sqlite mysql; do
    echo "== $driver"
    phpunit8.1 --bootstrap=tests/bootstrap-$driver.php tests/
done;
