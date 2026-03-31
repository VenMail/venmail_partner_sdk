#!/bin/bash
# Venmail DirectAdmin Hook: Post Domain Removal

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP=$(which php 2>/dev/null || echo "/usr/local/bin/php")

"$PHP" "$SCRIPT_DIR/../exec/venmail_provision.php" terminate "$domain" 2>/dev/null || true
