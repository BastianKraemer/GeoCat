#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
TEST_DIR="$DIR/../test/"

php "$TEST_DIR/phpunit.phar" --bootstrap "$TEST_DIR/php/TestHelper.php" "$TEST_DIR/php/"
