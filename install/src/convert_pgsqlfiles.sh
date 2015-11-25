#!/bin/bash
sed -i 's/\"//g' ../sql/pgsql.setup.sql
sed -i 's/\"//g' ../sql/pgsql.cleanup.sql
