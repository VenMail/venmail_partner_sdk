#!/bin/bash
# Build standalone WHMCS module ZIP for marketplace submission
# Usage: bash build-whmcs.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="$SCRIPT_DIR/dist/whmcs"
ZIP_NAME="venmail-whmcs-module.zip"

echo "Building Venmail WHMCS module..."

# Clean
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/modules/servers/venmail/lib"
mkdir -p "$BUILD_DIR/modules/servers/venmail/templates"
mkdir -p "$BUILD_DIR/modules/servers/venmail/lang"

# Copy main module files
cp "$SCRIPT_DIR/whmcs/modules/servers/venmail/venmail.php" "$BUILD_DIR/modules/servers/venmail/"
cp "$SCRIPT_DIR/whmcs/modules/servers/venmail/hooks.php" "$BUILD_DIR/modules/servers/venmail/"
cp "$SCRIPT_DIR/whmcs/modules/servers/venmail/templates/clientarea.tpl" "$BUILD_DIR/modules/servers/venmail/templates/"
cp "$SCRIPT_DIR/whmcs/modules/servers/venmail/lang/english.php" "$BUILD_DIR/modules/servers/venmail/lang/"

# Bundle the API client directly (standalone — no SDK dependency)
cp "$SCRIPT_DIR/lib/VenmailApi.php" "$BUILD_DIR/modules/servers/venmail/lib/"

# Copy logo if exists
if [ -f "$SCRIPT_DIR/whmcs/modules/servers/venmail/logo.png" ]; then
    cp "$SCRIPT_DIR/whmcs/modules/servers/venmail/logo.png" "$BUILD_DIR/modules/servers/venmail/"
fi

# Create ZIP
cd "$BUILD_DIR"
rm -f "$SCRIPT_DIR/dist/$ZIP_NAME"
zip -r "$SCRIPT_DIR/dist/$ZIP_NAME" modules/

echo ""
echo "Built: dist/$ZIP_NAME"
echo "Upload this ZIP to the WHMCS Marketplace."
