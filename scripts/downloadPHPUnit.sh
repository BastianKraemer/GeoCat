#!/bin/bash

# =============================================================================
# File: downloadPHPUnit.sh
#
# This script downloads phpUnit
# =============================================================================

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

curl -o $DIR/../test/phpunit.phar -L https://phar.phpunit.de/phpunit.phar
chmod +x $DIR/../test/phpunit.phar
