#!/bin/bash

# =============================================================================
# File: prepareTestEnvironment.sh
#
# This script prepares the GeoCat test environment.
# =============================================================================

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
TEST_DIR="$DIR/../test/"

PHP_RUN="php"

echo "Checking environment..."

if [ ! -f $TEST_DIR/testconfig.php ]; then
	echo "GeoCat Test Configuration file 'testconfig.php' does not exist at '/test/'."
	echo "Aborting..."
	exit 1
fi

if [ ! -f $TEST_DIR/phpunit.phar ]; then
	echo "Downloading phpUnit..."
	$($DIR/downloadPHPUnit.sh)
fi

printf "\nTest environment successfully checked.\n\n"
printf "Preparing database...\n\n"


echo "Running 'CLEANUP'"
echo "------------------------------------------------------------"
echo
$PHP_RUN "$DIR/../install/geocat.php" setup --config "$TEST_DIR/testconfig.php" --delete -v

printf "\n\n"
echo "Running 'CREATE DATABASE'"
echo "------------------------------------------------------------"
echo
$PHP_RUN "$DIR/../install/geocat.php" setup --config "$TEST_DIR/testconfig.php" --install -v

echo
echo "------------------------------------------------------------"
echo "FINISHED"
echo "------------------------------------------------------------"
