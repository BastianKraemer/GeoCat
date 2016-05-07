# Configure GeoCat test environment

## Prepare environment

Follow these instructions to prepare the GeoCat test environment:

- Copy the GeoCat confiuration file from 'src/config/config.php' to 'test/testconfig.php'
- Change the name of database name in 'testconfig.php' to the new value
- Download phpUnit (you may want to use '/scripts/downloadPHPUnit.sh')

## Run '/scripts/prepareTestEnvironment.sh'

This script will create the database mentioned in the 'testconfig.php' file
If not alrady done, it will also download phpUnit for you.

WARNING: Executing this script will delete all data in the test database.

## Running tests

To run the tests you can use the script 'scripts/runTests.sh'
