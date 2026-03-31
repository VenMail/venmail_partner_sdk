#!/bin/bash
# Venmail cPanel Plugin Uninstaller

set -e

echo "Uninstalling Venmail cPanel Plugin..."

/usr/local/cpanel/bin/manage_hooks delete script /usr/local/venmail/venmail_hooks.php 2>/dev/null || true

rm -rf /usr/local/cpanel/base/frontend/jupiter/venmail
rm -rf /usr/local/cpanel/whostmgr/docroot/cgi/venmail
rm -rf /usr/local/venmail

echo "Venmail cPanel Plugin uninstalled. Config at /etc/venmail.conf preserved."
