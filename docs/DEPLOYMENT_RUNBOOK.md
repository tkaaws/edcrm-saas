# EDCRM SaaS Deployment Runbook

This document is the frozen reference for setting up and deploying the `edcrm-saas` project on a fresh DigitalOcean droplet.

Use this when:

- creating a new droplet
- setting up the server from scratch
- explaining the deployment flow to a new engineer
- verifying that the GitHub-to-droplet pipeline is working

## Final Architecture

Current deployment flow:

1. local development on Windows
2. push code to GitHub
3. GitHub Actions connects to the droplet over SSH
4. droplet runs deploy script
5. deploy script pulls latest code, installs Composer dependencies, fixes `writable/`, and reloads services
6. app is served by Nginx with PHP-FPM

## Project And Server Details

- Local project path: `E:\xampp\htdocs\edcrm-saas`
- GitHub repository: `https://github.com/tkaaws/edcrm-saas`
- Droplet hostname: `edcrm-app-01`
- Droplet IP: `143.110.247.79`
- Server path: `/var/www/edcrm-saas`
- OS: Ubuntu 24.04.4 LTS
- Web server: Nginx
- PHP runtime: PHP 8.4
- PHP process manager: `php8.4-fpm`
- Framework: CodeIgniter 4.7.2

## One-Time Server Setup

### 1. SSH into the droplet

From Windows PowerShell:

```powershell
ssh deploy@143.110.247.79
```

### 2. Create the app directory

Run on the droplet:

```bash
sudo mkdir -p /var/www/edcrm-saas
sudo chown -R deploy:deploy /var/www/edcrm-saas
cd /var/www/edcrm-saas
```

### 3. Clone the repository

Run on the droplet:

```bash
git clone https://github.com/tkaaws/edcrm-saas.git .
```

If the repository was empty during first clone, push the project from local and then run:

```bash
cd /var/www/edcrm-saas
git pull origin main
```

### 4. Install PHP dependencies

Run on the droplet:

```bash
cd /var/www/edcrm-saas
composer install --no-dev --optimize-autoloader
```

### 5. Create `.env`

Run on the droplet:

```bash
cd /var/www/edcrm-saas
cp env .env
```

### 6. Install and start PHP-FPM

Run on the droplet:

```bash
sudo apt update
sudo apt install -y php8.4-fpm
sudo systemctl enable php8.4-fpm
sudo systemctl start php8.4-fpm
```

### 7. Configure Nginx

Create the site config:

```bash
sudo nano /etc/nginx/sites-available/edcrm-saas
```

Use this configuration:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name _;

    root /var/www/edcrm-saas/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and disable the default site:

```bash
sudo ln -s /etc/nginx/sites-available/edcrm-saas /etc/nginx/sites-enabled/edcrm-saas
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 8. Fix writable permissions

Run on the droplet:

```bash
sudo chown -R www-data:www-data /var/www/edcrm-saas/writable
sudo chmod -R 775 /var/www/edcrm-saas/writable
```

## Manual Deployment Commands

If auto-deploy is not available, use:

```bash
cd /var/www/edcrm-saas
git pull origin main
composer install --no-dev --optimize-autoloader
sudo chown -R www-data:www-data /var/www/edcrm-saas/writable
sudo chmod -R 775 /var/www/edcrm-saas/writable
sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx
```

## GitHub Actions Auto-Deploy

Auto-deploy files in the repository:

- `.github/workflows/deploy.yml`
- `scripts/deploy.sh`
- `.github/DEPLOYMENT.md`

### GitHub Secrets Required

In GitHub repository settings, add these secrets:

- `DROPLET_HOST`
  - value: `143.110.247.79`
- `DROPLET_USER`
  - value: `deploy`
- `DROPLET_SSH_KEY`
  - value: full private key content for the SSH key used by `deploy`

The private key will look like:

```text
-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----
```

### SSH Requirement

The matching public key must exist on the droplet in:

```bash
/home/deploy/.ssh/authorized_keys
```

### Make the deploy script executable

Run once on the droplet:

```bash
cd /var/www/edcrm-saas
chmod +x scripts/deploy.sh
```

### Allow non-interactive sudo for deploy user

Open sudoers safely:

```bash
sudo visudo
```

Add this line:

```text
deploy ALL=(ALL) NOPASSWD: /usr/bin/chown, /usr/bin/chmod, /usr/bin/systemctl reload php8.4-fpm, /usr/bin/systemctl reload nginx
```

Save and exit.

This is required because the deploy script uses `sudo` during GitHub Actions execution.

## Verification Checklist

### 1. Verify repository on droplet

```bash
cd /var/www/edcrm-saas
git remote -v
git branch
ls
```

### 2. Verify Composer dependencies

```bash
cd /var/www/edcrm-saas
ls vendor
php spark routes
```

Expected:

- `vendor/` exists
- `php spark routes` works

### 3. Verify Nginx config

```bash
sudo nginx -t
sudo nginx -T | grep -n "root "
```

Expected:

```text
root /var/www/edcrm-saas/public;
```

### 4. Verify local HTTP response

```bash
curl http://127.0.0.1 | head
```

### 5. Verify public browser response

Open in browser:

```text
http://143.110.247.79/
```

### 6. Verify auto-deploy

1. make a small visible text change locally
2. commit and push to `main`
3. do not run `git pull` manually
4. check GitHub Actions
5. refresh the browser

If the browser updates after the workflow runs, auto-deploy is working.

## Known Good State

The following has already been proven:

- droplet created successfully
- SSH access works
- project code is on the droplet
- Composer dependencies install correctly
- Nginx serves `/var/www/edcrm-saas/public`
- PHP-FPM executes the app correctly
- writable permission issue was fixed
- CodeIgniter app is live in browser
- GitHub Actions-based auto-deploy updated the homepage text successfully

## Common Problems And Fixes

### Problem: `E:\xampp\htdocs\edcrm-saas: command not found`

Cause:

- Windows path was typed into Ubuntu shell

Fix:

- use Linux paths such as `/var/www/edcrm-saas`

### Problem: `vendor/` missing

Cause:

- `composer install` not yet run on droplet

Fix:

```bash
cd /var/www/edcrm-saas
composer install --no-dev --optimize-autoloader
```

### Problem: Nginx shows `Welcome to nginx!`

Cause:

- default site still enabled or wrong document root

Fix:

- disable default site
- enable `edcrm-saas` site
- set root to `/var/www/edcrm-saas/public`

### Problem: `500 Internal Server Error`

Cause:

- most likely writable permission issue

Fix:

```bash
sudo chown -R www-data:www-data /var/www/edcrm-saas/writable
sudo chmod -R 775 /var/www/edcrm-saas/writable
```

### Problem: GitHub Actions deploy fails with sudo prompt

Cause:

- `deploy` user cannot run deploy-time sudo commands non-interactively

Fix:

- add the sudoers rule shown above with `visudo`

## Recommended Next Phase

Now that deployment is frozen and repeatable, move to product work in this order:

1. create and configure `.env` properly
2. provision DigitalOcean database
3. add DB connection
4. design multi-tenant CRM data model
5. start implementation in `edcrm-saas`
6. use `jbkcrm` as reference source, not as the deployed codebase
