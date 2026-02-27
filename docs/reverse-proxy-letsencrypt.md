# Central Reverse Proxy + Let's Encrypt

Goal:
- one central Nginx reverse proxy for multiple Docker projects
- TLS via Let's Encrypt (Certbot) with automatic renewal
- current project reachable under `/todo`
- `/todo` prefix is stripped before forwarding
- `/` should return `404`

## Architecture

Two separate stacks are used:

1. Todo backend stack (this repository)
- runs app + database
- does not expose public HTTP ports in production
- joins shared external Docker network `edge` with alias `todo-backend`

2. Edge proxy stack (`infra/edge-proxy/`)
- runs public Nginx on ports `80/443`
- runs Certbot for certificate issuance and renewals
- routes `/todo/*` to `todo-backend:80`

## Prerequisites

- DNS `ja-geil-gleggmire.duckdns.org` points to `194.35.120.105`
- inbound firewall open for TCP `80` and `443`
- production secrets exist in `.env.prod.local` (`APP_SECRET`, `JWT_PASSPHRASE`, `MAILER_DSN`)

## Step 1: Create shared edge network (once)

```bash
docker network create edge || true
```

## Step 2: Start todo backend stack

From repository root:

```bash
docker compose \
  -f compose.yaml \
  -f compose.prod.yaml \
  -f compose.prod.local.yaml \
  -f compose.edge.yaml \
  up -d --build
```

This attaches the `php` service to external network `edge` with alias `todo-backend`.

## Step 3: Start central proxy stack

```bash
cd infra/edge-proxy
cp compose.env.example compose.env
```

Edit `compose.env` and set at least:

```dotenv
DOMAIN=ja-geil-gleggmire.duckdns.org
LETSENCRYPT_EMAIL=you@example.com
LETSENCRYPT_STAGING=0
```

Start proxy:

```bash
docker compose up -d --build
```

Watch logs:

```bash
docker compose logs -f reverse-proxy certbot
```

## Expected behavior

- `https://ja-geil-gleggmire.duckdns.org/todo` redirects to `/todo/`
- `https://ja-geil-gleggmire.duckdns.org/todo/*` forwards to backend without `/todo` prefix
- `https://ja-geil-gleggmire.duckdns.org/` returns `404`

Example:
- `https://ja-geil-gleggmire.duckdns.org/todo/api/doc`

## Add more applications later

1. In another project, attach app service to external network `edge`.
2. Assign a unique network alias (example `notes-backend`).
3. Add route file in `infra/edge-proxy/nginx/routes/notes.conf`.
4. Reload Nginx:

```bash
cd infra/edge-proxy
docker compose exec reverse-proxy nginx -s reload
```

Template route:

```nginx
location = /notes {
    return 301 /notes/;
}

location /notes/ {
    proxy_pass http://notes-backend:80/;
    proxy_http_version 1.1;

    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Port $server_port;
    proxy_set_header X-Forwarded-Prefix /notes;
    proxy_set_header Connection "";
}
```
