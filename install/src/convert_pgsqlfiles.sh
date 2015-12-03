#!/bin/bash
sed -i 's/\"//g' ../sql/pgsql.setup.sql
#sed -i 's/\"//g' ../sql/pgsql.cleanup.sql
sed -i 's/TINYINT/SMALLINT/g' ../sql/pgsql.setup.sql
