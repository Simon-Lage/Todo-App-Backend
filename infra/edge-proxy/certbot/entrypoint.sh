#!/bin/sh
set -eu

DOMAIN="${DOMAIN:-}"
EMAIL="${LETSENCRYPT_EMAIL:-}"
STAGING="${LETSENCRYPT_STAGING:-0}"
RENEW_INTERVAL="${CERTBOT_RENEW_INTERVAL_SECONDS:-43200}"
BOOTSTRAP_RETRY="${CERTBOT_BOOTSTRAP_RETRY_SECONDS:-300}"

WEBROOT="/var/www/certbot"
LIVE_DIR="/etc/letsencrypt/live/$DOMAIN"
ACTIVE_CERT_DIR="/certs"

if [ -z "$DOMAIN" ]; then
  echo "[certbot] DOMAIN is not set. Configure DOMAIN in compose.env."
  exit 1
fi

if [ -z "$EMAIL" ]; then
  echo "[certbot] LETSENCRYPT_EMAIL is not set. Configure LETSENCRYPT_EMAIL in compose.env."
  exit 1
fi

mkdir -p "$WEBROOT" "$ACTIVE_CERT_DIR"

sync_active_cert() {
  if [ -s "$LIVE_DIR/fullchain.pem" ] && [ -s "$LIVE_DIR/privkey.pem" ]; then
    cp "$LIVE_DIR/fullchain.pem" "$ACTIVE_CERT_DIR/fullchain.pem"
    cp "$LIVE_DIR/privkey.pem" "$ACTIVE_CERT_DIR/privkey.pem"
    chmod 644 "$ACTIVE_CERT_DIR/fullchain.pem"
    chmod 600 "$ACTIVE_CERT_DIR/privkey.pem"
    echo "[certbot] Active certificate synced for $DOMAIN."
    return 0
  fi

  return 1
}

request_initial_cert() {
  set -- certbot certonly \
    --webroot \
    -w "$WEBROOT" \
    --agree-tos \
    --non-interactive \
    --email "$EMAIL" \
    --keep-until-expiring \
    --rsa-key-size 4096 \
    -d "$DOMAIN"

  if [ "$STAGING" = "1" ]; then
    set -- "$@" --test-cert
    echo "[certbot] Using Let's Encrypt staging endpoint."
  fi

  "$@"
}

renew_certificates() {
  certbot renew --webroot -w "$WEBROOT" --quiet
}

until sync_active_cert; do
  echo "[certbot] No Let's Encrypt certificate available yet. Trying initial issuance for $DOMAIN."
  if request_initial_cert; then
    sync_active_cert || true
  else
    echo "[certbot] Initial issuance failed. Retrying in $BOOTSTRAP_RETRY seconds."
    sleep "$BOOTSTRAP_RETRY"
  fi
done

while :; do
  echo "[certbot] Running periodic renewal check."
  renew_certificates || echo "[certbot] Renewal check failed; retrying in next cycle."
  sync_active_cert || true
  sleep "$RENEW_INTERVAL"
done
