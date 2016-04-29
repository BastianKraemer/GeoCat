#!/bin/bash

# =============================================================================
# File: genBase64ImageCSSFile.sh
#
# This script generates a CSS file with the BASE64 encoded equivalents of the
# all png-files located in $IMG_DIR.
# =============================================================================

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

IMG_DIR="$DIR/../src/img"
OUTPUT="$DIR/../src/css/geocat-images.css"

printf "/* CSS File for BASE64 encoded images\n" > $OUTPUT
printf "   NOTE: This File is generated automatically - do not edit */\n\n" >> $OUTPUT

for f in $(ls $IMG_DIR/*.png); do
	echo "$f..."

	PREFIX=img-
	FILENAME=$(basename "$f")
	CSSCLASSNAME=$PREFIX${FILENAME%.*}

	printf ".$CSSCLASSNAME{\nbackground-image: url(data:image/png;base64,$(base64 -w0 "$f"));\n}\n" >> $OUTPUT
done