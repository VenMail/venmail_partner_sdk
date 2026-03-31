#!/bin/bash
# Venmail cPanel Plugin Installer
# Run as root: bash install.sh

set -e

PLUGIN_DIR="/usr/local/cpanel/base/frontend/jupiter/venmail"
WHM_DIR="/usr/local/cpanel/whostmgr/docroot/cgi/venmail"
HOOKS_DIR="/var/cpanel/perl/Cpanel/Hooks"
CONFIG_FILE="/etc/venmail.conf"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SDK_DIR="$(dirname "$SCRIPT_DIR")"

echo "Installing Venmail cPanel Plugin..."

# Copy shared API client
mkdir -p /usr/local/venmail
cp "$SDK_DIR/lib/VenmailApi.php" /usr/local/venmail/

# Install hooks
mkdir -p "$(dirname "$HOOKS_DIR")"
cp "$SCRIPT_DIR/hooks/venmail_hooks.php" /usr/local/venmail/

# Install cPanel user interface
mkdir -p "$PLUGIN_DIR"
cp "$SCRIPT_DIR/user/venmail/"* "$PLUGIN_DIR/" 2>/dev/null || true

# Install WHM admin interface
mkdir -p "$WHM_DIR"
cp "$SCRIPT_DIR/admin/venmail/"* "$WHM_DIR/" 2>/dev/null || true

# Create config if not exists
if [ ! -f "$CONFIG_FILE" ]; then
    cat > "$CONFIG_FILE" << 'EOF'
# Venmail cPanel Plugin Configuration
# Get your API key from: https://m.venmail.io/partner/settings
api_url=https://m.venmail.io
api_key=YOUR_PARTNER_API_KEY_HERE
EOF
    chmod 600 "$CONFIG_FILE"
    echo "Created $CONFIG_FILE — please edit with your API key."
fi

# Register hooks with cPanel
/usr/local/cpanel/bin/manage_hooks add script /usr/local/venmail/venmail_hooks.php 2>/dev/null || true

echo "Venmail cPanel Plugin installed successfully!"
echo ""
echo "Next steps:"
echo "  1. Edit $CONFIG_FILE with your Venmail partner API key"
echo "  2. If you don't have a partner account, sign up via the Venmail API:"
echo "     POST https://m.venmail.io/api/v1/provisioning/auth/signup"
echo ""
