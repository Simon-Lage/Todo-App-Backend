#!/bin/sh
set -e

JWT_DIR="/var/jwt"
JWT_PRIVATE_KEY="$JWT_DIR/private.pem"
JWT_PUBLIC_KEY="$JWT_DIR/public.pem"

if [ ! -f "$JWT_PRIVATE_KEY" ] || [ ! -f "$JWT_PUBLIC_KEY" ]; then
    echo "Generating JWT keypair..."
    mkdir -p "$JWT_DIR"
    
    if [ -n "${JWT_PASSPHRASE:-}" ]; then
        php bin/console lexik:jwt:generate-keypair --skip-if-exists
    else
        export JWT_PASSPHRASE=""
        php bin/console lexik:jwt:generate-keypair --skip-if-exists
    fi
    
    if [ -f "config/jwt/private.pem" ]; then
        mv config/jwt/private.pem "$JWT_PRIVATE_KEY"
        mv config/jwt/public.pem "$JWT_PUBLIC_KEY"
    fi
    
    chmod 600 "$JWT_PRIVATE_KEY"
    chmod 644 "$JWT_PUBLIC_KEY"
    chown www-data:www-data "$JWT_PRIVATE_KEY" "$JWT_PUBLIC_KEY" 2>/dev/null || true
fi

exec docker-php-entrypoint "$@"
