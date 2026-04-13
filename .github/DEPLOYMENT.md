# Deployment Setup

This repository supports automatic deployment to the DigitalOcean droplet through GitHub Actions.

## Workflow

On every push to `main`, GitHub Actions will:

1. SSH into the droplet
2. Run `/var/www/edcrm-saas/scripts/deploy.sh`
3. Pull the latest code
4. Run Composer in production mode
5. Fix `writable/` permissions
6. Reload `php8.4-fpm` and `nginx`

## Required GitHub Secrets

Add these repository secrets in GitHub:

- `DROPLET_HOST`
  - example: `143.110.247.79`
- `DROPLET_USER`
  - example: `deploy`
- `DROPLET_SSH_KEY`
  - private SSH key that can log into the droplet as `deploy`

## Server Assumptions

The workflow assumes:

- app path is `/var/www/edcrm-saas`
- branch is `main`
- PHP-FPM service is `php8.4-fpm`
- Nginx service is `nginx`

## First-Time Server Preparation

Make the deploy script executable on the droplet:

```bash
cd /var/www/edcrm-saas
chmod +x scripts/deploy.sh
```

If `deploy` cannot run `sudo` non-interactively for the commands in the script, add a sudoers rule for:

- `chown`
- `chmod`
- `systemctl reload php8.4-fpm`
- `systemctl reload nginx`

## Manual Fallback

If GitHub Actions is not available yet, deploy manually with:

```bash
cd /var/www/edcrm-saas
bash scripts/deploy.sh main
```
