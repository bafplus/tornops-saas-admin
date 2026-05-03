# TornOps SaaS Admin - Setup Instructions

## Quick Setup (on your VPS with Docker)

```bash
# 1. Clone the repo
git clone https://github.com/bafplus/tornops-saas-admin.git
cd tornops-saas-admin

# 2. Create Laravel project
composer create-project laravel/laravel . --prefer-dist --force

# 3. Create .env file
cp .env.example .env
php artisan key:generate

# 4. Configure environment
nano .env
# Set:
# APP_NAME="TornOps SaaS"
# APP_URL=https://tornops.net
# DB_CONNECTION=sqlite (or mysql for production)

# 5. Run migrations
php artisan migrate
```

## Docker Deployment

```bash
# Build image
docker build -t tornops-saas-admin .

# Run container
docker run -d \
  --name tornops-saas \
  -p 8080:80 \
  -v /data/tornops:/data/tornops \
  -e MASTER_KEY=your-secure-key \
  -e TORNOPS_IMAGE=ghcr.io/bafplus/tornops/tornops:latest \
  -e DATA_VOLUME_PATH=/data/tornops \
  tornops-saas-admin
```

## Key Routes

| URL | Purpose |
|-----|---------|
| `/` | Landing page + order form |
| `/admin` | Admin dashboard |
| `/admin/factions` | Faction management |

## Environment Variables

```env
# Required
MASTER_KEY=generate-secure-random-key
TORNOPS_IMAGE=ghcr.io/bafplus/tornops/tornops:latest
DATA_VOLUME_PATH=/data/tornops

# Optional (for payment monitoring)
TORN_API_KEY=your-torn-api-key
TORN_API_ID=your-player-id
PAYMENT_ITEM_ID=item-id-to-check
```

## Current Directory Structure

```
tornops-saas-admin/
├── SPEC.md                    # Specification
├── config/app.php             # Configuration
├── app/Models/Faction.php   # Faction model
├── app/Services/DockerService.php  # Docker management
└── (Laravel scaffold to add)
```

## What's Needed

1. Run `composer create-project laravel/laravel .`
2. Complete routes/web.php
3. Create controllers
4. Create views
5. Add payment monitoring cron job