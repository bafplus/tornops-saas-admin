# TornOps SaaS - Complete History

## What is TornOps SaaS?
Multi-tenant version of TornOps (Torn.com faction management) sold as a service. Each faction gets isolated instance via subdomain (factionname.tornops.net).

## Architecture
- **SaaS Admin**: Runs on port 8081 at tornops.net
- **Instances**: Each faction = PHP process in `/home/server/tornops-instances/SLUG/`
- **Routing**: Cloudflare Zero Trust tunnel to VPS port 8081
- **Database**: SQLite per instance

## Server Access
- **VPS IP**: 217.154.154.7:2222
- **User**: server
- **Key**: /home/bart/.ssh/id_ed25519 *or* use opencode on VPS

## Cloudflare Credentials
- Email: bart.cockheyt@gmail.com
- API Key: d888ebd7a3d4da09d50f35b1c6dea49d92f96
- Zone ID: 677fb6d5519e5c230f1de82aba4e8cb2 (tornops.net)
- Tunnel ID: b5999d7d-67f0-461f-9513-7b8dcb15766d
- Account ID: 7ea602d1cd797bf3cb34885c9d240114
- Tunnel routes to: tornops.net→217.154.154.7:8081

## Master Key
- **SaaS Admin**: 110381
- Used via: `/master-login?master_key=110381`

## Directories
- SaaS Admin app: `/home/server/tornops-saas-admin`
- Instance storage: `/home/server/tornops-instances`
- Create script: `/home/server/tornops-saas-admin/scripts/create_faction.php`

## Features Completed
1. ✅ SaaS Admin panel at tornops.net (port 8081)
2. ✅ Master key login: `/master-login?master_key=110381`
3. ✅ Cloudflare DNS auto-creation (CNAME record)
4. ✅ Manual faction creation
5. ✅ Factions table with status, port, log columns
6. ✅ Create form at `/admin/factions/create`
7. ✅ Output streaming in create form

## Known Issues
1. **Delete button 404**: CSRF/session issue - needs master_key bypass route
2. **Cloudflare tunnel route**: Not automatically added - needs manual API call
3. **Port conflicts**: Script sometimes picks wrong port

## Quick Fix Commands

### Restart SaaS Admin server
```bash
cd /home/server/tornops-saas-admin
pkill -f 'php.*:8081'
php artisan serve --host=0.0.0.0 --port=8081 &
```

### Test master login
```bash
curl "http://localhost:8081/master-login?master_key=110381"
```

### Add Cloudflare tunnel route (AFTER instance is running)
```bash
# Get current config
curl -s "https://api.cloudflare.com/client/v4/accounts/7ea602d1cd797bf3cb34885c9d240114/cfd_tunnel/b5999d7d-67f0-461f-9513-7b8dcb15766d/configurations" \
  -H "X-Auth-Email: bart.cockheyt@gmail.com" \
  -H "X-Auth-Key: d888ebd7a3d4da09d50f35b1c6dea49d92f96"

# Add new route (replace SLUG and PORT)
curl -X PUT "https://api.cloudflare.com/client/v4/accounts/7ea602d1cd797bf3cb34885c9d240114/cfd_tunnel/b5999d7d-67f0-461f-9513-7b8dcb15766d/configurations" \
  -H "X-Auth-Email: bart.cockheyt@gmail.com" \
  -H "X-Auth-Key: d888ebd7a3d4da09d50f35b1c6dea49d92f96" \
  -H "Content-Type: application/json" \
  -d '{"config":{"ingress":[{"hostname":"tornops.net","service":"http://217.154.154.7:8081"},{"hostname":"SLUG.tornops.net","service":"http://217.154.154.7:PORT"},{"hostname":"jouwpasjouwdata.nl","service":"http://217.154.154.7:80"},{"hostname":"mytachodata.com","service":"http://217.154.154.7:80"},{"service":"http_status:404"}]}}'
```

### Run create script
```bash
php /home/server/tornops-saas-admin/scripts/create_faction.php SLUG MASTERKEY
```

### Sync from local machine
```bash
rsync -az -e 'ssh -p 2222' --delete /home/bart/tornops-saas-admin/ server@217.154.154.7:/home/server/tornops-saas-admin/
```

## To Fix Later
1. Update create_faction.php to also call Cloudflare tunnel API to add ingress route
2. Fix delete button to work with session (currently 404)
3. Make port detection more reliable

## Files Modified Local
- /home/bart/tornops-saas-admin/scripts/create_faction.php
- /home/bart/tornops-saas-admin/routes/web.php
- /home/bart/tornops-saas-admin/app/Http/Controllers/FactionController.php
- /home/bart/tornops-saas-admin/resources/views/admin/factions.blade.php
- /home/bart/tornops-saas-admin/resources/views/admin/factions/create.blade.php