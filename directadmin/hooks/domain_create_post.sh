#!/bin/bash
# Venmail DirectAdmin Hook: Post Domain Creation
# Called by DirectAdmin after a new domain is created.
# Environment variables: $domain, $username, $email

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP=$(which php 2>/dev/null || echo "/usr/local/bin/php")

"$PHP" "$SCRIPT_DIR/../exec/venmail_provision.php" create "$domain" "$username" "$email" 2>/dev/null || true
