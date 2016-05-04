#!/bin/bash

# =============================================================================
# File: checkTranslations.sh
#
# This script compares the translations files to checks if there are any
# duplicate or missing keys
# =============================================================================

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

createTempFile() {
	cut -d = -f 1 "$1" | sed '/^#/d' | sed '/^$/d' | sort > "$2"
}

checkForDuplicateKeys(){
	NUM_LINES=$(cat "$1" | wc -l)
	NUM_UNIQE_LINES=$(cat "$1" | uniq | wc -l)
	
	if [ $NUM_LINES -ne $NUM_UNIQE_LINES ]; then
		printf "  > File '%s' has duplicate keys in it\n" "$1"
		
		printf "    Diff:\n";
		cat "$1" | uniq | diff "$1" - | sed 's/^/        /'
		printf "\n\n"
	fi
}

diffFiles(){
	printf "Comparing '%s' and '%s'... " "$1" "$2"
	DIFF_RESULT=$(diff "$1" "$2")
	
	if [ -z "$DIFF_RESULT" ]; then
		printf "ok.\n"
	else
		printf "difference detected.\n" 
		echo "$DIFF_RESULT" | sed 's/^/    /'
	fi
}

LOCALE_DIR="$DIR/../src/locale"
OUTPUT="$DIR/../src/css/geocat-images.css"

TMP_DE="$DIR/de.tmp"
TMP_DE_CLIENT="$DIR/de_client.tmp"
TMP_EN="$DIR/en.tmp"

echo "Scanning files..."

createTempFile "$LOCALE_DIR/de.properties" "$TMP_DE"
createTempFile "$LOCALE_DIR/de_client.properties" "$TMP_DE_CLIENT"
createTempFile "$LOCALE_DIR/en.properties" "$TMP_EN"

echo "Checking for duplicate keys..."

checkForDuplicateKeys "$TMP_DE"
checkForDuplicateKeys "$TMP_DE_CLIENT"
checkForDuplicateKeys "$TMP_EN"

echo "Performing diff..."

diffFiles "$TMP_DE" "$TMP_EN"

# Clean up
rm "$TMP_DE"
rm "$TMP_DE_CLIENT"
rm "$TMP_EN"
