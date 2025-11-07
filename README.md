# start project
docker compose up -d

# create demo data
docker compose exec php bin/console app:dev:seed-random-data --purge

# API Documentation (Dev-Mode)
# Swagger UI: https://localhost:8443/api/doc
# JSON: https://localhost:8443/api/doc.json