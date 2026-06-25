# Cloudflare + Nginx production security setup

The intended request path is:

```text
Visitor -> Cloudflare -> Nginx -> PHP-FPM -> Laravel
```

Cloudflare is the only public reverse proxy. Nginx accepts
`CF-Connecting-IP` only when the connection came from an official Cloudflare
network, then replaces its client address with the visitor address. Laravel
uses that resulting address for logs and application rate limits.

Do not configure Laravel to trust every proxy and do not use
`TRUSTED_PROXIES=*`.

## 1. Configure the production environment

Set these values in `/var/www/eindejaarsbbq/.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://eindejaarsbbq.nl

TRUSTED_HOSTS=eindejaarsbbq.nl,www.eindejaarsbbq.nl
ALLOWED_ORIGINS=https://eindejaarsbbq.nl,https://www.eindejaarsbbq.nl
TRUSTED_PROXIES=

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

`TRUSTED_PROXIES` stays empty because Nginx restores the visitor address
before passing the request to PHP-FPM.

After changing `.env`:

```bash
cd /var/www/eindejaarsbbq
php artisan optimize:clear
php artisan optimize
```

## 2. Configure Cloudflare DNS

In Cloudflare DNS:

1. Point the `eindejaarsbbq.nl` and `www` records at the origin server.
2. Enable the orange-cloud **Proxied** status for both records.
3. Remove old DNS records that reveal a previous origin IP.
4. Do not publish an unproxied hostname pointing at the same server.

## 3. Configure Cloudflare TLS

In **SSL/TLS -> Overview**, select **Full (strict)**.

The origin certificate must be unexpired and valid for
`eindejaarsbbq.nl` and `www.eindejaarsbbq.nl`. The existing Let's Encrypt
certificate paths in the Nginx configuration satisfy this when the
certificate is valid.

Also enable:

- **Always Use HTTPS**
- TLS 1.3
- A minimum TLS version appropriate for the visitors; TLS 1.2 is the
  compatibility-friendly choice

Do not use Cloudflare's **Flexible** mode.

## 4. Install the Nginx configuration

Create the required directories if they do not exist:

```bash
sudo mkdir -p /etc/nginx/conf.d /etc/nginx/snippets
```

Copy the Cloudflare trust configuration first:

```bash
cd /var/www/eindejaarsbbq
sudo cp deploy/nginx-cloudflare-real-ip.conf \
  /etc/nginx/conf.d/cloudflare-real-ip.conf
sudo cp deploy/nginx-rate-limits.conf \
  /etc/nginx/conf.d/eindejaarsbbq-rate-limits.conf
```

Then install the site:

```bash
sudo cp deploy/nginx-eindejaarsbbq.conf \
  /etc/nginx/sites-available/eindejaarsbbq
sudo ln -sfn /etc/nginx/sites-available/eindejaarsbbq \
  /etc/nginx/sites-enabled/eindejaarsbbq
```

Validate before reloading:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Do not reload Nginx if `nginx -t` reports an error.

The files in `/etc/nginx/conf.d` must be included from Nginx's `http`
context. This is the standard layout on Debian and Ubuntu.

Optional origin-only Nginx blocking is provided in
`deploy/nginx-cloudflare-only.conf`, but do not enable it until the site is
confirmed reachable through Cloudflare and direct-origin blocking is already
working at the firewall. If enabled too early, it can lock out legitimate
traffic on systems where the real-IP module or include order differs.

## 5. Restrict the server firewall

Nginx already rejects non-Cloudflare requests in its named public server
blocks. The firewall should provide an additional layer.

Before changing SSH rules, identify the administration IP address or VPN
network and keep the current SSH session open.

For UFW, use this pattern:

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow from YOUR_ADMIN_IP to any port 22 proto tcp
```

Add port 80 and 443 rules for every network listed in:

- https://www.cloudflare.com/ips-v4/
- https://www.cloudflare.com/ips-v6/

Example for one network:

```bash
sudo ufw allow from 173.245.48.0/20 to any port 80 proto tcp
sudo ufw allow from 173.245.48.0/20 to any port 443 proto tcp
```

Repeat for all IPv4 and IPv6 ranges before enabling UFW:

```bash
sudo ufw status numbered
sudo ufw enable
sudo ufw status verbose
```

If SSH is managed through Cloudflare Tunnel, a VPN, or another firewall,
adapt the SSH rule accordingly. Never enable a deny-by-default firewall
until administration access has been allowed and tested.

## 6. Add Cloudflare edge protections

Cloudflare should absorb abusive traffic before it reaches the dedicated
server. Recommended dashboard settings:

1. Enable the managed WAF rules available for the plan.
2. Enable bot protection or Bot Fight Mode when available.
3. Add a rate-limit rule for `POST /login`.
4. Add a rate-limit rule for `POST /aanmelden`.
5. Optionally challenge suspicious traffic to `/dashboard*`.

Cloudflare limits are the first layer. Nginx and Laravel limits remain
enabled because edge rules should not be the only protection.

Start Cloudflare rules conservatively and inspect Security Events before
tightening them. Shared schools, offices, and mobile networks can place many
legitimate visitors behind one public IP.

## 7. Validate the real visitor IP

After deployment, make a normal request through the public hostname:

```bash
curl -I https://eindejaarsbbq.nl
```

Then inspect the Nginx access log:

```bash
sudo tail -n 20 /var/log/nginx/access.log
```

The logged client address should be the visitor address, not one of
Cloudflare's networks.

Test direct-origin blocking by substituting the server's public IP:

```bash
curl -Ik --resolve eindejaarsbbq.nl:443:ORIGIN_IP \
  https://eindejaarsbbq.nl/
```

Run that command from a machine outside Cloudflare. It should receive
`403 Forbidden` or fail at the firewall.

Finally verify:

```bash
cd /var/www/eindejaarsbbq
php artisan about
php artisan route:list --path=aanmelden
sudo nginx -t
```

## 8. Maintain the Cloudflare network list

Cloudflare can change its published address ranges. During server
maintenance, compare these locations:

- `/etc/nginx/conf.d/cloudflare-real-ip.conf`
- firewall rules

against:

- https://www.cloudflare.com/ips-v4/
- https://www.cloudflare.com/ips-v6/

Update Nginx and the firewall together, run `sudo nginx -t`, and only then
reload Nginx.

For stronger origin authentication, Authenticated Origin Pulls can be
enabled later. IP allowlisting plus Full (strict) TLS is the simpler initial
deployment and must be working before adding mTLS.
