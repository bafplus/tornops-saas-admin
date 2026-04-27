# TornOps SaaS - Key Information

## Server Access
- IP: 217.154.154.7
- Port: 2222
- User: server
- Key: /home/bart/.ssh/id_ed25519

## URLs
- SaaS Admin: http://tornops.net (port 8081)
- Master Login: /master-login?master_key=110381

## Cloudflare
- Email: bart.cockheyt@gmail.com
- API Key: d888ebd7a3d4da09d50f35b1c6dea49d92f96
- Zone ID: 677fb6d5519e5c230f1de82aba4e8cb2
- Tunnel ID: b5999d7d-67f0-461f-9513-7b8dcb15766d
- Account ID: 7ea602d1cd797bf3cb34885c9d240114

## Directories
- SaaS Admin: /home/server/tornops-saas-admin
- Instances: /home/server/tornops-instances

## Known Issues to Fix
1. Delete button returns 404 - route not working
2. Cloudflare tunnel route not being added in create script
3. Need to add route manually after creation

## Quick Fix Commands

# Restart server
cd /home/server/tornops-saas-admin && php artisan serve --host=0.0.0.0 --port=8081

# Test master login
curl "http://localhost:8081/master-login?master_key=110381"

# Add Cloudflare tunnel route manually
curl -X PUT "https://api.cloudflare.com/client/v4/accounts/7ea602d1cd797bf3cb34885c9d240114/cfd_tunnel/b5999d7d-67f0-461f-9513-7b8dcb15766d/configurations" \
  -H "X-Auth-Email: bart.cockheyt@gmail.com" \
  -H "X-Auth-Key: d888ebd7a3d4da09d50f35b1c6dea49d92f96" \
  -H "Content-Type: application/json" \
  -d '{"config":{"ingress":[{"hostname":"SLUG.tornops.net","service":"http://217.154.154.7:PORT"},{"hostname":"tornops.net","service":"http://217.154.154.7:8081"},JOYWASD_ETC]}}'

# Run create faction script
php /home/server/tornops-saas-admin/scripts/create_faction.php SLUG MASTERKEY

# Full git sync from local
rsync -az -e 'ssh -p 2222' --delete /home/bart/tornops-saas-admin/ server@217.154.154.7:/home/server/tornops-saas-admin/