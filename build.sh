#!/usr/bin/env bash
set -e

REMOTE_URL="http://releases.kcalto.com/wordpress"

mkdir -p ./build
cp ./kcalto.php ./build
cp -R ./src ./build

INFOFILE="releases/info.json"

VERSION=$(jq -r '.version' $INFOFILE)
echo "Building version '$VERSION'..."

FILENAME="releases/packages/kcalto-$VERSION.zip"

if test -f "$FILENAME"; then
  echo "Version '$VERSION' already exists, aborting.\nUpdate 'version' string in './releases/info.json' to continue."
  exit 1
fi

cp -R ./build ./kcalto
zip -r $FILENAME ./kcalto
rm -rf ./kcalto

DATE=$(date '+%Y-%m-%d %H:%M:%S')
echo "$(jq --arg v "$DATE" '.last_updated = $v' $INFOFILE)" > $INFOFILE
echo "$(jq --arg v "$REMOTE_URL/packages/kcalto-$VERSION.zip" '.download_url = $v' $INFOFILE)" > $INFOFILE