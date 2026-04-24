# TornOps

> ⚠️ **Early Development Warning**  
> This project is currently in early stages of development. It may not function correctly or have complete features. Use at your own risk.

TornOps is a web portal for Torn City faction members to aggregate, analyze, and visualize data from Torn City APIs and third-party services.

## Features

- **Ranked War Tracking** - Monitor faction wars and attack statistics
- **Member Analytics** - Track faction member performance and stats
- **Multi-API Integration** - Torn API, FFScouter, TornStats
- **Auto-Sync** - Scheduled data synchronization
- **Member Portal** - Individual user dashboards and settings
- **Admin Panel** - Full control over faction settings and API keys

## Tech Stack

- **Framework:** Laravel 11
- **PHP:** 8.3
- **Database:** SQLite (file-based, no server required)
- **Web Server:** Apache (in Docker)
- **Container:** Docker (single container deployment)

## Requirements

- Docker
- Torn API Key

## Quick Start (Prebuilt Image)

### 1. Create data folder

```bash
mkdir tornops-data && cd tornops-data
```

### 2. Create .env file

```bash
cat > .env << 'EOF'
TORN_API_KEY=your_torn_api_key_here
DB_CONNECTION=sqlite
DB_DATABASE=/data/database.sqlite
EOF
```

Get your Torn API key from: https://www.torn.com/preferences.php#tab=api

### 3. Run container

```bash
docker run -v ./tornops-data:/data -p 8080:80 ghcr.io/bafplus/tornops/tornops:latest
```

### 4. Access application

- **Portal:** http://localhost:8080
- **Setup:** http://localhost:8080/setup (first time only)

The first time you run the container, it will automatically:
- Clone the latest code from GitHub
- Install dependencies
- Create the SQLite database
- Run migrations

## Updating

To update to the latest version:

```bash
# Pull latest Docker image
docker pull ghcr.io/bafplus/tornops/tornops:latest

# Restart container (automatically pulls latest code from GitHub)
docker restart tornops
```

That's it! The container will:
1. Pull the latest code from GitHub
2. Run any new database migrations
3. Seed any new default data

## Data Persistence

All data is stored in your mapped `./tornops-data` folder:

```
tornops-data/
├── .env              # Your configuration (backup this!)
├── app/              # Application code (can be deleted to reset)
└── database.sqlite   # Your data (backup this!)
```

## Docker Compose (Alternative)

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  tornops:
    image: ghcr.io/bafplus/tornops/tornops:latest
    container_name: tornops
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./tornops-data:/data
```

Then run:

```bash
docker compose up -d
```

## Development / Building Locally

If you want to build the image yourself:

```bash
# Clone repository
git clone https://github.com/bafplus/tornops.git
cd tornops

# Build image
docker build -t tornops .

# Test locally
mkdir -p test-data
echo "TORN_API_KEY=test" > test-data/.env
docker run -v ./test-data:/data -p 8080:80 tornops
```

## Configuration

### Torn API Key
1. Go to https://www.torn.com/preferences.php#tab=api
2. Create a custom key with selections:
   - Faction: members, rankedwarreport, warfare, wars, rankedwars
   - User: profile, attacks, battlestats, personalstats, basic

### Faction Settings (Admin Panel)
- Faction ID
- Torn API Key
- Auto-sync toggle

## Logs

```bash
docker logs tornops
```

## License

MIT License