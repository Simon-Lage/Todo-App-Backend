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
- `JWT_PASSPHRASE`: Optional passphrase for JWT key encryption (empty by default in dev)
- See `compose.env` for database credentials

### Production
For production, set a strong JWT_PASSPHRASE in your environment:
```bash
export JWT_PASSPHRASE="your-strong-passphrase-here"
```