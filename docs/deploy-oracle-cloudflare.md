# Deploy: Portfolio to Oracle Cloud + Cloudflare + SSL

## Architecture

```
Internet
  │
  ▼
┌─────────────────────────────────────────────────────┐
│  Cloudflare (proxy)                                  │
│  • DNS: A record → Oracle VM public IP              │
│  • SSL: Full (or Full Strict)                       │
│  • WAF, DDoS, Cache, CDN                            │
│  • Origin: your domain (e.g. novotek.com.ua)        │
│  • Port 443 in → 443 out (or 8080)                  │
└────────────────────────────┬────────────────────────┘
                             │ TLS
                             ▼
┌─────────────────────────────────────────────────────┐
│  Oracle Cloud VM (Ubuntu 22.04)                     │
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │  Caddy (TLS reverse proxy)                  │   │
│  │  :443 → 127.0.0.1:8080                      │   │
│  │  • Auto TLS (or use Cloudflare cert)        │   │
│  │  • HSTS                                     │   │
│  │  • Rate-limit (optional)                    │   │
│  └──────────────────┬──────────────────────────┘   │
│                     │ HTTP                          │
│                     ▼                               │
│  ┌─────────────────────────────────────────────┐   │
│  │  Docker Compose (production)                │   │
│  │  nginx :80 → php-fpm :9000                  │   │
│  │  mysql:3306  redis:6379                    │   │
│  │  messenger (worker)                         │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  Firewall (Oracle NSG + ufw):                      │
│    • 443/tcp  — allow (Cloudflare IPs only, or *)  │
│    • 80/tcp   — allow (Cloudflare IPs only, or *)  │
│    • 22/tcp   — allow (your IP only)               │
│    • 8080/tcp — DO NOT expose (localhost only)     │
└─────────────────────────────────────────────────────┘
```

**Key decisions:**
- **Cloudflare** handles DDoS, WAF, CDN, bot protection. TLS can be terminated at Cloudflare (SNI) or at the origin.
- **Caddy** on the VM provides a local TLS layer (defense in depth) and HSTS. If you use Cloudflare SSL mode "Full (strict)", Caddy gets real TLS. If "Full", Caddy still works (it accepts both).
- **Docker Compose prod** runs nginx + php + mysql + redis + messenger. The nginx container binds to `127.0.0.1:8080` (already configured in `docker-compose.prod.yml`).
- **Symfony CSP subscriber** sets Content-Security-Policy. Caddy/nginx must NOT override it.

---

## 1. Oracle Cloud VM Setup

### 1.1 Create the VM

1. Go to **Oracle Cloud Console** → **Compute** → **Instances** → **Create Instance**.
2. **Image**: Ubuntu 22.04 (or 24.04).
3. **Shape**: `VM.Standard.A1.Flex` with **2 OCPUs, 4 GB RAM, 20 GB block storage** (minimum for MySQL + Redis + PHP + Node).
   - If budget allows: 4 OCPUs / 8 GB RAM.
4. **Networking**: assign a **public IPv4** (or attach an existing one).
5. **SSH**: paste your public key.
6. **Network Security Group** (NSG) — **Inbound Rules**:

| Source | Protocol | Port | Description |
|--------|----------|------|-------------|
| `0.0.0.0/0` | TCP | 80 | Cloudflare → origin (HTTP) |
| `0.0.0.0/0` | TCP | 443 | Cloudflare → origin (HTTPS) |
| `YOUR_HOME_IP/32` | TCP | 22 | SSH (restrict to your IP) |

> **Important**: Do NOT open port 8080 to the internet. It's bound to `127.0.0.1` inside the VM.

7. **Outbound Rules**: allow all (default).

### 1.2 Initial OS Setup

```bash
# SSH into the VM
ssh ubuntu@YOUR_VM_IP

# Update
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
newgrp docker

# Install Caddy
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/deb.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install -y caddy

# Install ufw (additional firewall layer)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose

# Install git (needed for repo)
sudo apt install -y git make

# Disable swap (optional, improves MySQL performance)
sudo swapoff -a
sudo sed -i '/swap/s/^/#/' /etc/fstab
```

### 1.3 Create App User & Directories

```bash
sudo useradd -m -s /bin/bash deploy
sudo usermod -aG docker deploy
sudo mkdir -p /opt/portfolio
sudo chown deploy:deploy /opt/portfolio
```

---

## 2. Deploy the Application

### 2.1 Clone & Configure

```bash
# Switch to deploy user
su - deploy

# Clone the repo
git clone https://github.com/dyar74/my_business_card /opt/portfolio
cd /opt/portfolio

# Create the production env file
cd portfolio-docker
make init    # creates .env.docker with random secrets

# Edit .env.docker — fill in required values:
nano .env.docker
```

**Required values in `.env.docker`:**

```env
HTTP_PORT=8080
APP_VERSION=latest
XDEBUG_MODE=off

# MySQL (keep the auto-generated passwords)
MYSQL_DATABASE=portfolio
MYSQL_USER=portfolio
MYSQL_PASSWORD=<auto-generated>
MYSQL_ROOT_PASSWORD=<auto-generated>

REDIS_PASSWORD=<auto-generated>

# Symfony
APP_SECRET=<auto-generated>

# n8n
N8N_BASE_URL=https://n8n.novotek.com.ua
N8N_WEBHOOK_SECRET=<your-webhook-secret>
N8N_HMAC_KEY=<your-hmac-key>
N8N_API_KEY=<your-n8n-api-key>

# Cloudflare Turnstile (optional)
TURNSTILE_SECRET=

# Contact
CONTACT_TO_EMAIL=info@novotek.com.ua

# Production mailer (required for prod)
MAILER_DSN=smtp://user:pass@smtp.provider.com:587
```

### 2.2 Build & Start (Production)

```bash
cd /opt/portfolio/portfolio-docker

# Build production images (takes 5-10 min first time)
make prod-build

# Run migrations (explicit deploy step)
make prod-migrate

# Start the stack
make prod-up

# Verify
make prod-logs
docker compose --env-file .env.docker -f ../docker-compose.prod.yml ps
```

Expected output:
```
portfolio-nginx-1      Up   (healthy)
portfolio-php-1        Up   (healthy)
portfolio-messenger-1  Up
portfolio-mysql-1      Up   (healthy)
portfolio-redis-1      Up   (healthy)
```

### 2.3 Verify Locally

```bash
# From the VM itself:
curl -s http://127.0.0.1:8080/en/ | head -20
curl -s http://127.0.0.1:8080/api/health
```

Expected:
```json
{"site":"ok","n8n":"ok","latencyMs":123}
```

---

## 3. Configure Caddy (TLS + HSTS)

### 3.1 Caddyfile

Create `/etc/caddy/Caddyfile`:

```caddyfile
# Your domain (adjust as needed)
yourdomain.com,
www.yourdomain.com {
    # Proxy to Docker nginx
    reverse_proxy 127.0.0.1:8080

    # HSTS (only set this if TLS is terminated here or at Cloudflare
    # with "Full (strict)" mode. If Cloudflare handles TLS, this is
    # still correct and adds defense in depth.)
    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
        # Do NOT set CSP here — Symfony sets it.
        # Do NOT set X-Frame-Options here — nginx already does.
    }

    # Log to file
    log {
        output file /var/log/caddy/access.log
        format json
    }

    # Health check endpoint (optional, for monitoring)
    handle /healthz {
        respond "ok" 200
    }
}
```

### 3.2 Apply & Test

```bash
# Validate
sudo caddy validate --config /etc/caddy/Caddyfile

# Reload
sudo systemctl reload caddy

# Check status
sudo systemctl status caddy
```

> **TLS**: Caddy will automatically obtain a Let's Encrypt certificate for `yourdomain.com`.
> If you want Cloudflare to handle TLS instead, set Cloudflare SSL mode to **"Full (strict)"**
> and Caddy will present its own cert (which Cloudflare verifies). Or use Cloudflare's
> origin CA cert — but Let's Encrypt via Caddy is simpler.

### 3.3 Enable Caddy on Boot

```bash
sudo systemctl enable caddy
```

---

## 4. Cloudflare Configuration

### 4.1 DNS

1. Log in to **Cloudflare** → your domain → **DNS** → **Records**.
2. Add (or update) these records:

| Type | Name | Content | TTL | Proxy |
|------|------|---------|-----|-------|
| `A` | `@` | `YOUR_VM_PUBLIC_IP` | Auto | **Proxied** (orange cloud) |
| `A` | `www` | `YOUR_VM_PUBLIC_IP` | Auto | **Proxied** (orange cloud) |
| `CNAME` | `api` | `yourdomain.com` | Auto | **Proxied** |

> If `n8n.novotek.com.ua` is on a different domain, no CNAME needed.

### 4.2 SSL/TLS

1. **SSL/TLS** tab → **Overview** → Set mode to:
   - **"Full (strict)"** — if Caddy has a valid cert (recommended).
   - **"Full"** — if you want Cloudflare to accept self-signed (not recommended).
   - **"Flexible"** — **DO NOT USE** (breaks HTTPS, causes redirect loops).

2. **Edge Certificates**:
   - ✅ Always Use HTTPS
   - ✅ Minimum TLS Version: **1.2**
   - ✅ HSTS (optional, Caddy already sets it)

### 4.3 Security

1. **Security** → **Bots**: enable "Bots" filter.
2. **Security** → **WAF**: enable default ruleset.
3. **Security** → **Rate Limiting** (optional, Symfony already rate-limits):
   - Add rule: `/api/*` — 50 requests / 10 min per IP (Cloudflare-level, before reaching Symfony).

### 4.4 Caching

1. **Caching** → **Configuration**:
   - Browser Cache TTL: **4 hours**
   - Cache eligible HTTPS responses: **On**
   - Cache Query String: **Off** (or set to cache `*` for static assets only)

2. **Caching** → **Rules** (add):
   - **Rule**: Cache static assets
     - **Expression**: `uri.path path_matches "/build/*" or uri.path path_matches "/images/*"`
     - **Action**: Cache Everything
     - **TTL**: 1 year

3. **Caching** → **Rules** (add):
   - **Rule**: Don't cache API
     - **Expression**: `uri.path path_matches "/api/*"`
     - **Action**: Bypass Cache

### 4.5 Pages (optional)

- Enable **Always Online** for stale content during VM restarts.
- Enable **Development Mode** for testing (auto-expires after 10 min).

---

## 5. Verification

### 5.1 Health Check

```bash
# From your local machine:
curl -s https://yourdomain.com/api/health | jq .

# Expected:
# {
#   "site": "ok",
#   "n8n": "ok",
#   "latencyMs": 142
# }
```

### 5.2 SSL Check

```bash
# Check certificate chain:
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com 2>/dev/null | openssl x509 -noout -issuer -subject -dates

# Verify HSTS header:
curl -sI https://yourdomain.com/ | grep -i strict-transport
# Expected: Strict-Transport-Security: max-age=31536000; includeSubDomains; preload

# Verify security headers:
curl -sI https://yourdomain.com/ | grep -iE 'x-frame|x-content|referrer|permissions|cross-origin'
```

### 5.3 Performance

```bash
# PageSpeed: https://pagespeed.web.dev/ → enter your URL
# Target: Lighthouse ≥ 90 in all categories

# Lighthouse (CLI):
npx lighthouse https://yourdomain.com/en/ --output=json --output-path=./report.json --chrome-flags="--headless"
```

### 5.4 Demo Endpoints

```bash
# Test translate demo:
curl -s -X POST https://yourdomain.com/api/demo/ai-translate-en-uk \
  -H 'Content-Type: application/json' \
  -d '{"text":"Hello, how are you?","direction":"en-uk","tone":"neutral"}' | jq .

# Test rate limiting (make 6+ rapid requests):
for i in $(seq 1 7); do
  curl -s -o /dev/null -w "%{http_code} " -X POST \
    https://yourdomain.com/api/demo/ai-translate-en-uk \
    -H 'Content-Type: application/json' \
    -d '{"text":"test","direction":"en-uk","tone":"neutral"}'
done
# Expected: 200 200 200 200 200 429 429
```

---

## 6. Operations

### 6.1 Updates

```bash
cd /opt/portfolio

# Pull latest code
git pull

# Rebuild images
cd portfolio-docker
make prod-build

# Run migrations
make prod-migrate

# Restart
make prod-down
make prod-up
```

### 6.2 Logs

```bash
# Application logs:
make prod-logs

# Caddy logs:
sudo tail -f /var/log/caddy/access.log

# System logs:
sudo journalctl -u caddy -f
```

### 6.3 Backup

```bash
# MySQL dump:
cd /opt/portfolio/portfolio-docker
make backup
# Creates: backup-2025-01-01.sql.gz

# Restore:
gunzip -c backup-2025-01-01.sql.gz | docker compose --env-file .env.docker -f ../docker-compose.prod.yml exec -T mysql mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"
```

### 6.4 Monitoring

Add to `/etc/cron.d/monitoring`:

```
# Health check every 5 min
*/5 * * * * root curl -sf --max-time 10 https://yourdomain.com/api/health || echo "ALERT: portfolio unhealthy" | mail -s "Portfolio Alert" admin@yourdomain.com
```

Or use **Uptime Kuma** / **Better Stack** / **Datadog** for proper monitoring.

---

## 7. Troubleshooting

### 7.1 Site unreachable from Cloudflare

```bash
# On the VM:
# 1. Check Docker containers:
docker compose --env-file /opt/portfolio/portfolio-docker/.env.docker -f /opt/portfolio/docker-compose.prod.yml ps

# 2. Check nginx is listening:
ss -tlnp | grep 8080

# 3. Test locally:
curl -v http://127.0.0.1:8080/en/

# 4. Check Caddy:
sudo systemctl status caddy
sudo journalctl -u caddy -n 50

# 5. Check firewall:
sudo ufw status verbose
sudo iptables -L -n | grep -E '80|443|8080'

# 6. Check Oracle NSG:
# Console → Compute → Instances → your instance → Networking → NSG
# Ensure inbound rules allow 80 and 443 from Cloudflare IPs (or 0.0.0.0/0)
```

### 7.2 SSL errors

```
# Cloudflare error 526: Invalid SSL certificate
# → Caddy cert not valid. Check:
sudo openssl s_client -connect 127.0.0.1:443 -servername yourdomain.com 2>/dev/null | openssl x509 -noout -issuer

# Cloudflare error 521: Web server is down
# → VM unreachable. Check NSG + ufw + Docker.

# Cloudflare error 523: Origin is unreachable
# → DNS A record wrong. Check Cloudflare DNS → yourdomain.com A record.
```

### 7.3 Rate limiting not working

```bash
# Check Redis is up:
docker compose --env-file /opt/portfolio/portfolio-docker/.env.docker -f /opt/portfolio/docker-compose.prod.yml exec redis redis-cli -a "<REDIS_PASSWORD>" ping
# Expected: PONG

# Check limiter keys:
docker compose ... exec redis redis-cli -a "<REDIS_PASSWORD>" keys "*rate_limiter*"
```

### 7.4 n8n health showing "down"

```bash
# Test n8n reachability from the VM:
curl -s https://n8n.novotek.com.ua/healthz | jq .

# If it works from VM but not from browser, the issue is DNS/firewall
# between Cloudflare and n8n.novotek.com.ua.
```

---

## 8. Cost Estimate (Oracle Cloud)

| Resource | Shape | Monthly Cost (Always Free) |
|----------|-------|---------------------------|
| VM | VM.Standard.A1.Flex (2 OCPU, 4 GB) | $0 (Always Free tier) or ~$50/mo (paid) |
| Block Storage | 20 GB | $0 (Always Free) or ~$3/mo |
| Public IP | 1 IPv4 | $0 (Always Free) or ~$10/mo |
| **Total** | | **$0 (Always Free)** or **~$63/mo** |

**Cloudflare**: Free plan is sufficient (100 GB bandwidth, basic WAF, DDoS protection).
Pro plan ($20/mo) adds advanced WAF, page rules, and better analytics.

---

## Quick-Start Cheat Sheet

```bash
# 1. VM setup (5 min)
sudo apt update && sudo apt upgrade -y
curl -fsSL https://get.docker.com | sh
sudo apt install -y caddy git make

# 2. App deploy (10 min)
git clone https://github.com/dyar74/my_business_card /opt/portfolio
cd /opt/portfolio/portfolio-docker
make init
nano .env.docker          # fill in secrets
make prod-build
make prod-migrate
make prod-up

# 3. Caddy (2 min)
sudo nano /etc/caddy/Caddyfile   # add your domain
sudo systemctl reload caddy

# 4. Cloudflare (5 min)
# DNS: A record → VM IP (proxied)
# SSL: Full (strict)
# Security: Bots + WAF on

# 5. Verify
curl -s https://yourdomain.com/api/health
curl -sI https://yourdomain.com/ | grep -i strict-transport
```
