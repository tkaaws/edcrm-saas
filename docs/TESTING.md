# EDCRM Testing Guide

This project uses automated testing as a gate before deployment.

## Why we have it

We want to catch:

- broken routes
- auth and permission bugs
- schema or migration issues
- service-layer regressions
- bad seed/setup changes

before code reaches the droplet.

## Current testing layers

### 1. Fast PHPUnit suites

These run first:

- `unit`
- `feature`
- `session`

They are used for:

- route protection
- redirects
- guard/filter behavior
- lightweight service logic

These are fast and do not depend on the live droplet database.

### 2. Database PHPUnit suite

This runs after the fast suites:

- `database`

It uses a separate temporary MySQL database created inside GitHub Actions:

- database name: `edcrm_test`
- host: `127.0.0.1`
- user: `root`

This is not production data and not the droplet database.

For this suite, the test runner:

1. creates a clean temporary test database
2. runs app migrations into that database
3. seeds demo/test data
4. runs service and integration tests against that isolated database

This helps us verify real behavior with:

- schema
- models
- services
- session setup
- permissions

## Current test coverage

Today the test suite covers these areas:

- protected routes and guest redirects
- password-reset redirect enforcement
- auth service database flows
- tenant access policy logic
- impersonation rules
- user access boundary rules
- route protection for branch/platform policy areas

Coverage will keep expanding as new modules are built.

## Deployment flow

When code is pushed to `main`:

1. GitHub Actions runs fast tests
2. GitHub Actions runs database tests
3. only if tests pass, deploy starts
4. droplet deploy script pulls code, installs dependencies, and runs migrations

So tests are a deployment gate.

## Important note about seeding

Automated deploy runs migrations on the droplet.

Automated deploy does **not** run `DatabaseSeeder` on production.

That is intentional because demo data should not be inserted on every live deploy.

## Local developer usage

### Run fast suites locally

```bash
vendor/bin/phpunit --testsuite unit,feature,session --no-coverage --do-not-cache-result
```

### Run database suite locally

You need:

- a local MySQL test database
- `.env` values for the `tests.db.*` connection

Then run:

```bash
vendor/bin/phpunit --testsuite database --no-coverage --do-not-cache-result
```

## What is coming later

Later we will add Playwright for browser and end-to-end testing.

That layer will cover real user journeys like:

- login
- tenant creation
- role creation
- user creation
- impersonation
- settings save flows
- future CRM workflows

## Rule going forward

As we add modules, we should add tests in parallel.

The goal is:

- logic tests for rules
- database tests for real integration
- browser tests later for real user flows

This file should be kept current as the testing stack grows.
