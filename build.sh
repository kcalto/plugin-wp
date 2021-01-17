#!/usr/bin/env bash
set -e

REMOTE_URL="http://releases.kcalto.com/wordpress"
INFOFILE="./info.template.json"
TARGET_INFOFILE="releases/info.json"

# Obtain target version string
VERSION=$(jq -r '.version' $INFOFILE)
echo "Building version '$VERSION'..."

FILENAME="releases/packages/kcalto-$VERSION.zip"
FILENAME_LATEST="releases/packages/latest.zip"

# Add a way to know plugin's version
touch ./src/version.php
truncate -s 0 ./src/version.php
echo "<?php define('KCALTO_CURRENT_VERSION', '$VERSION');\n" > ./src/version.php

# Up is the only way forward
# if test -f "$FILENAME"; then
#   echo "Version '$VERSION' already exists, aborting.\nUpdate 'version' string in '$INFOFILE' to continue."
#   exit 1
# fi

# Build plugin deployment pkg
mkdir -p ./build
cp ./kcalto.php ./build
cp -R ./src ./build

# Move deployment pkg to releases
cp -R ./build ./kcalto
zip -r $FILENAME ./kcalto
cp $FILENAME $FILENAME_LATEST
rm -rf ./kcalto

# Update remote info.json
cp $INFOFILE $TARGET_INFOFILE
DATE=$(date '+%Y-%m-%d %H:%M:%S')
echo "$(jq --arg v "$DATE" '.last_updated = $v' $TARGET_INFOFILE)" > $TARGET_INFOFILE
echo "$(jq --arg v "$REMOTE_URL/packages/kcalto-$VERSION.zip" '.download_url = $v' $TARGET_INFOFILE)" > $TARGET_INFOFILE