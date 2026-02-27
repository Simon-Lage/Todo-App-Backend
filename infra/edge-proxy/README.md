# Edge Reverse Proxy (separate stack)

This is a standalone Nginx + Certbot stack for URI-based routing and central TLS termination.

## 1) Prepare environment

```bash
cd infra/edge-proxy
cp compose.env.example compose.env
# edit compose.env and set LETSENCRYPT_EMAIL
```

## 2) Create shared Docker network once

```bash
docker network create edge || true
```

## 3) Start proxy stack

```bash
docker compose up -d --build
```

## 4) Check logs

```bash
docker compose logs -f reverse-proxy certbot
```

## Routing

- `/todo/*` -> upstream `todo-backend:80` (configured in `nginx/routes/todo.conf`)
- `/` -> `404`

## Add another app

1. Attach that app to external network `edge`.
2. Give the app service a unique alias (example: `notes-backend`).
3. Add a new route file in `nginx/routes/`.
4. Reload proxy:

```bash
docker compose exec reverse-proxy nginx -s reload
```
