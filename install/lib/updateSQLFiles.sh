#!/bin/bash

# =============================================================================
#
# File: updateSQLFiles.sh
#
# The SQL files in '../sql' has been generated using the
# "WWW SQL Designer" (https://github.com/ondras/wwwsqldesigner),
# because it supports SQL output for MySQL and PostgreSQL.
#
# Because of some issues with the output files this script corrects some
# statements and removes unnecessary comment lines
# =============================================================================

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

MYSQL_SETUP_FILE="$DIR/../sql/mysql.setup.sql"
TMP_FILE="$DIR/../sql/setup.sql.tmp"

PGSQL_SETUP_FILE="$DIR/../sql/pgsql.setup.sql"

# Update MySQL files

echo "Updateing MySQL setup file..."

if [ -e "$MYSQL_SETUP_FILE" ]; then

	sed -i "s/\`password\` VARCHAR(64) NULL DEFAULT NULL/\`password\` VARCHAR(64) NULL/" "$MYSQL_SETUP_FILE"
	sed -i "s/\`salt\` VARCHAR(32) NULL DEFAULT NULL/\`salt\` VARCHAR(32) NULL/" "$MYSQL_SETUP_FILE"
	sed -i "s/\`email\` VARCHAR(64) NULL DEFAULT NULL/\`email\` VARCHAR(64) NULL/" "$MYSQL_SETUP_FILE"

	# Remove all lines after "-- Table Properties"
	START_COMMENTS=`grep -n '\-\- Table Properties' "$MYSQL_SETUP_FILE" | cut -d : -f 1`

	if [ -n "$START_COMMENTS" ]; then
		LINE_CNT=`expr $START_COMMENTS - 2`

		head -n $LINE_CNT "$MYSQL_SETUP_FILE" > "$TMP_FILE"
		rm "$MYSQL_SETUP_FILE"
		mv "$TMP_FILE" "$MYSQL_SETUP_FILE"
	fi
else
	echo "ERROR: Cannot to find file '$MYSQL_SETUP_FILE'"
fi

echo "Updateing PostgreSQL setup file..."

if [ -e "$PGSQL_SETUP_FILE" ]; then

	sed -i 's/\"//g' "$PGSQL_SETUP_FILE"
	sed -i 's/TINYINT/SMALLINT/g' "$PGSQL_SETUP_FILE"
else
	echo "ERROR: Cannot find file '$PGSQL_SETUP_FILE'"
fi
