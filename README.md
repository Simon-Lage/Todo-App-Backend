# Todo-App Backend

## Setup

### 1. Start the project
```bash
docker compose up -d --wait
```

**First start:** JWT keypair will be automatically generated in `/var/jwt/` inside the container (persisted in Docker volume `jwt_keys`).

### 2. Create demo data
```bash
docker compose exec php bin/console app:dev:seed-random-data --purge
```

### 3. Access API Documentation
- **Swagger UI**: https://localhost:8443/api/doc
- **JSON**: https://localhost:8443/api/doc.json

## JWT Authentication

JWT keys are stored in `/var/jwt/` inside the container and persisted via Docker volume.

**Manual key regeneration (if needed):**
```bash
docker compose exec php bin/console lexik:jwt:generate-keypair --overwrite
docker compose exec php mv config/jwt/*.pem /var/jwt/
docker compose restart php
```

## Environment Configuration

### Application Config (`config/packages/app.yaml`)
- `app.allowed_email_domains`: List of allowed email domains for registration

### Environment Variables

#### Required for Password Reset
- `PASSWORD_RESET_TOKEN_TTL`: Token validity in seconds (default: 3600 = 1 hour)
- `PASSWORD_RESET_URL_TEMPLATE`: Frontend URL template with `%s` placeholder for token
  - Example: `http://localhost:8100/auth/reset-password/confirm?token=%s`
- `MAILER_DSN`: Mailer configuration
  - Development: `smtp://mailpit:1025` (Mailpit is included in compose.override.yaml)
  - Production: Your SMTP server (e.g., `smtp://user:pass@smtp.example.com:587`)

#### JWT Configuration
- `JWT_PASSPHRASE`: Optional passphrase for JWT key encryption (empty by default in dev)
- `JWT_ACCESS_TTL`: Access token TTL in seconds (default: 3600)
- `JWT_REFRESH_TTL`: Refresh token TTL in seconds (default: 2592000 = 30 days)

#### Optional
- `GOOGLE_STUDIO_API_KEY`: API key used for Google Gemini requests
- See `compose.env` for database credentials

### Development Setup
The development environment (`compose.override.yaml`) includes:
- **Mailpit** on port 8025 (web UI) and 1025 (SMTP) for email testing
- Default values for all password reset variables
- Access Mailpit web UI at: http://localhost:8025

### Production
For production, set these environment variables:
```bash
export JWT_PASSPHRASE="your-strong-passphrase-here"
export PASSWORD_RESET_URL_TEMPLATE="http://194.35.120.105:8443/auth/reset-password/confirm?token=%s"
export MAILER_DSN="smtp://user:pass@smtp.example.com:587"
```

**Note:** The default `PASSWORD_RESET_URL_TEMPLATE` uses IP address `194.35.120.105` on port `8443`. Adjust if your frontend runs on a different port or if you set up a domain later.

See `.env.example` for a complete template.
