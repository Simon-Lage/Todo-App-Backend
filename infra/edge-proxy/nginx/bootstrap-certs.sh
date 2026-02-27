#!/bin/sh
set -eu

CERT_DIR="/etc/nginx/certs"
FULLCHAIN="$CERT_DIR/fullchain.pem"
PRIVKEY="$CERT_DIR/privkey.pem"
CN="${BOOTSTRAP_CERT_CN:-localhost}"

mkdir -p "$CERT_DIR"

if [ ! -s "$FULLCHAIN" ] || [ ! -s "$PRIVKEY" ]; then
  echo "[reverse-proxy] No active certificate found. Generating temporary self-signed certificate for $CN."
  openssl req -x509 -nodes -newkey rsa:2048 -days 2 \
    -keyout "$PRIVKEY" \
    -out "$FULLCHAIN" \
    -subj "/CN=$CN"
fi
