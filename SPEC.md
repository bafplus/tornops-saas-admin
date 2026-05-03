# TornOps SaaS Admin - Specification

## Overview
Multi-tenant TornOps management system. Spawns and manages TornOps containers for multiple factions.

## Architecture
```
Cloudflare → tornops.net (Main Admin Site)
              │
    ┌─────────┼─────────┐
    │         │         │
  /TRR/    /ABC/    /XYZ/  → Proxied to faction containers
    │         │         │
    ▼         ▼         ▼
TornOps   TornOps   TornOps   (Docker containers)
```

## Core Features

### 1. Landing Page (`/`)
- Pricing display
- Order form (faction name, email, Torn faction ID)
- Status: pending/active/cancelled

### 2. Payment Monitoring
- Poll Torn API: `/user/{id}/logstransfers`
- Identify payment by: item ID + optional comment
- Auto-detect new payments → create faction
- Store last_checked timestamp

### 3. Faction Management
- List all factions with status
- Create new faction container
- Start/Stop/Delete container
- Regenerate master key
- Mark as free/trial
- Set expiration date

### 4. Support Login
- "Login as [Faction]" button
- Redirects to: `https://tornops.net/{slug}/master-login?master_key={key}`

## Database Schema

### Table: factions
| Column | Type | Description |
|--------|------|-------------|
| id | int | Primary key |
| slug | string | URL slug (unique) |
| name | string | Faction name |
| torn_faction_id | int | Torn faction ID |
| status | string | pending/active/suspended/cancelled |
| is_trial | boolean | Free trial flag |
| monthly_cost | int | Cost in currency |
| payment_item_id | int | Item ID to monitor |
| container_name | string | Docker container name |
| db_path | string | Path to DB file |
| master_key | string | Unique master key |
| master_key_generated_at | datetime | When key was generated |
| last_login_at | datetime | Last support login |
| created_at | datetime | Creation date |
| expires_at | datetime | Subscription expiry |

## Environment Variables
```
TORNOPS_IMAGE=ghcr.io/bafplus/tornops/tornops:latest
DATA_VOLUME_PATH=/data/tornops
DEFAULT_MASTER_KEY= (auto-generated)
TUNNEL_TOKEN= (if using Cloudflare)
TORN_API_KEY= (for payment monitoring)
TORN_API_ID=  (your player ID)
PAYMENT_ITEM_ID= (item ID to check)
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | / | Landing page |
| GET | /admin | Admin dashboard |
| GET | /admin/factions | Faction list |
| POST | /admin/factions | Create faction |
| POST | /admin/factions/{id}/start | Start container |
| POST | /admin/factions/{id}/stop | Stop container |
| POST | /admin/factions/{id}/delete | Delete faction |
| POST | /admin/factions/{id}/regenerate-key | New master key |
| POST | /admin/factions/{id}/login | Support login redirect |
| POST | /webhook/payment | Payment webhook (optional) |

## Docker Integration
- Use Docker API to spawn containers
- Each faction: `docker run -d --name tornops-{slug} -v {slug}:/var/www/html/storage tornops-image`
- Environment vars set per container

## File Structure
```
tornops-saas-admin/
├── app/
│   ├── Console/Commands/
│   ├── Http/Controllers/
│   │   ├── AdminController.php
│   │   ├── FactionController.php
│   │   ├── PaymentController.php
│   │   └── PageController.php
│   ├── Models/
│   │   ├── Faction.php
│   │   └── Payment.php
│   └── Services/
│       ├── DockerService.php
│       └── TornPaymentService.php
├── config/
├── database/migrations/
├── routes/
│   └── web.php
└── resources/views/
    ├── home.blade.php
    └── admin/
        ├── layout.blade.php
        ├── factions.blade.php
        └── faction-detail.blade.php
```

## Payment Flow
1. User orders via form
2. System creates pending faction
3. Admin approves or user pays
4. Payment detected → status = active
5. Spawn Docker container
6. Create DB file
7. Send welcome info

## Support Login Flow
1. Admin clicks "Login" on faction
2. System redirects to: `https://tornops.net/{slug}/master-login?master_key={key}`
3. TornOps auto-logs in as admin