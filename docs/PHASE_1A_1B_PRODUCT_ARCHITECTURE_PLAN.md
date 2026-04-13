# EDCRM SaaS Phase 1A / 1B Product Architecture Plan

## 1. Executive Summary

This document defines the formal execution plan for the first two platform phases of `edcrm-saas`.

- **Phase 1A** establishes the multi-tenant SaaS foundation
- **Phase 1B** establishes billing catalog, entitlements, limits, and subscription enforcement engine

The platform is intended for multiple institutes, each with one or more branches, each branch operating its own leads, admissions, service, and placement workflows. The product must scale beyond small institutes and support regionally distributed operations, including different timezone and currency requirements.

The recommended v1 architecture is:

- **shared database multi-tenancy**
- **hybrid-ready service boundaries** so large customers can later move to isolated databases if needed
- **tenant-level defaults with branch-level overrides** for timezone and currency
- **base plan + module add-ons + capacity tiers** for billing
- **privilege-based user access + entitlement-based tenant access**

This plan uses `jbkcrm` as a domain reference, but not as a schema or architecture template.

---

## 2. Product Context

### 2.1 Business model

EDCRM SaaS is a platform company selling CRM capabilities to multiple institutes.

Examples:

- Upgrade
- Scalar
- future enterprise customers

Each institute can have:

- one tenant account
- many branches
- multiple departments
- multiple operational users
- institute-specific SMTP settings
- institute-specific WhatsApp integration
- branch-specific execution models

The platform owner also needs:

- platform admin access
- support access
- tenant oversight
- billing and entitlement control

### 2.2 Product direction

The product is a SaaS evolution of `jbkcrm`, with:

- cleaner schema
- better naming conventions
- scalable tenant-aware design
- simpler runtime model
- better UI and mobile responsiveness later
- future student portal integration
- future enterprise-ready evolution

### 2.3 Major business workflows expected later

The future module stack will cover:

- enquiry and lead capture
- followups
- admission conversion
- fees and installments
- service/tickets
- student lifecycle operations
- placement workflow
- reporting
- institute-specific communication setup

These are not implemented in Phase 1A/1B, but the architecture must be designed around them.

---

## 3. Architectural Principles

### 3.1 Multi-tenancy model

Use **shared database multi-tenancy** for v1.

All tenant-owned business tables must carry:

- `tenant_id`

Most operational tables must also carry:

- `branch_id`

Why:

- fastest path to market
- easiest to operate initially
- lowest deployment complexity
- appropriate for early SaaS growth

### 3.2 Hybrid-ready isolation

Although v1 is shared DB, code should be structured so larger customers can later move to isolated databases with minimum product-layer change.

That means:

- tenant resolution must be centralized
- repositories and services must be tenant-aware
- product logic must not directly assume one physical database forever
- no raw module code should bypass tenant scoping

### 3.3 Access control model

Access must be determined by two independent dimensions:

1. **Privilege**
   - what a user is allowed to do

2. **Entitlement**
   - what the tenant has purchased

Effective access is:

**tenant entitlement AND user privilege**

### 3.4 Platform admin rule

Platform admins do not automatically bypass tenant entitlements inside tenant workflows.

Rules:

- platform admins have full access in platform-management surfaces
- tenant operations still respect tenant subscription entitlements
- explicit support impersonation may be added later if tenant support requires controlled bypass behavior

### 3.5 Global standards

The platform must not assume India-only usage.

Global standards to support from the foundation:

- tenant default timezone
- tenant default currency
- tenant locale
- branch timezone override
- branch currency override
- globally safe naming conventions
- future internationalization readiness

### 3.6 Configuration strategy

Do not copy `jbkcrm`'s giant `settings` table model.

Instead:

- use structured settings for stable configuration
- use key-value extension table for flexible tenant-specific config
- keep SMTP and WhatsApp in dedicated tables

---

## 4. Phase 1A - Multi-Tenant Foundation

### 4.0 Implementation Status
Last updated: 2026-04-13
| # | Task | Status | Notes |
|---|------|--------|-------|
| 1 | Schema design | Done | 15 Phase 1A tables defined and aligned to multi-tenant naming |
| 2 | Migrations | Done | 15 migration files exist in app/Database/Migrations |
| 3 | Seeders | Done | DatabaseSeeder, PrivilegesSeeder, and DemoDataSeeder exist |
| 4 | BaseModel (tenant scoping) | Done | app/Models/BaseModel.php |
| 5 | Domain models | Done | TenantModel, BranchModel, UserModel, RoleModel, PrivilegeModel |
| 6 | TenantResolver service | Done | app/Services/TenantResolver.php |
| 7 | BranchContextResolver service | Done | app/Services/BranchContextResolver.php |
| 8 | PermissionService | Done | app/Services/PermissionService.php |
| 9 | CurrentUserContext service | Done | app/Services/CurrentUserContext.php |
| 10 | Locale and currency resolution | Done | handled through tenant and branch context services |
| 11 | Auth service and controller | In Progress | AuthService and Auth controller exist; seeded login validation still pending |
| 12 | Filters (Auth, Tenant, Suspension) | Done | filter classes exist and are registered in Filters.php |
| 13 | Auth and dashboard routes | Done | Routes.php exposes auth routes and protected /dashboard |
| 14 | Auth and dashboard starter views | Done | starter views added for login, forgot, reset, change password, and dashboard |
| 15 | Admin shell (layout, sidebar, topbar) | In Progress | responsive shell, sidebar, header, and dashboard layout added |
| 16 | User management CRUD | In Progress | list, create, edit, role assignment, branch assignment, and status toggle wired |
| 17 | Branch management CRUD | In Progress | list, create, edit, and status toggle wired |
| 18 | Role management CRUD | In Progress | list, create, edit, status toggle, and privilege assignment wired |
| 19 | Tenant settings (SMTP, WhatsApp) | In Progress | profile defaults, visibility modes, SMTP, and WhatsApp settings wired |
| 20 | TenantAccessPolicy / subscription policy integration | Pending | needed before full Phase 1B restriction enforcement |
| 21 | Platform tenant onboarding | In Progress | tenant list and tenant provisioning flow wired |
### 4.0.1 Handoff note
This table tracks repository implementation status, not just planning intent.
- Done means code/files exist and basic verification has been performed
- In Progress means the building blocks exist, but end-to-end validation is still pending
- Pending means not yet started or not yet ready for handoff
Current verified runtime facts:
- DigitalOcean droplet deployment pipeline is live
- Nginx + PHP-FPM are serving the app on http://143.110.247.79
- GitHub Actions deploy flow is working
- php spark routes succeeds locally with auth and dashboard routes registered
Current caution:
- do not assume demo credentials or migration execution state on every environment without re-checking seed and environment data first
---

### 4.1 Objectives

By the end of Phase 1A, the system must support:

- tenant creation
- branch creation under a tenant
- tenant-owned roles
- platform-defined privileges
- user creation and branch assignment
- hierarchy/manager mapping
- login and tenant-aware session handling
- branch-aware runtime context
- tenant-specific configuration storage
- SMTP and WhatsApp configuration per tenant

### 4.2 Domain entities

Phase 1A should introduce these entities:

- Tenant
- Branch
- Role
- Privilege
- RolePrivilege
- User
- UserBranch
- UserHierarchy
- TenantSettings
- TenantSettingValue
- TenantEmailConfig
- TenantWhatsappConfig
- PasswordResetToken
- UserPasswordHistory
- AuditLog

### 4.3 Recommended schema blueprint

#### `tenants`

Purpose:
- one row per institute/customer

Suggested fields:
- `id`
- `name`
- `slug`
- `status`
- `legal_name`
- `owner_name`
- `owner_email`
- `owner_phone`
- `default_timezone`
- `default_currency_code`
- `country_code`
- `locale_code`
- `created_at`
- `updated_at`

Suggested status values:
- `draft`
- `active`
- `suspended`
- `cancelled`

#### `tenant_branches`

Purpose:
- branches belonging to a tenant

Suggested fields:
- `id`
- `tenant_id`
- `name`
- `code`
- `type`
- `country_code`
- `state_code`
- `city`
- `address_line_1`
- `address_line_2`
- `postal_code`
- `timezone`
- `currency_code`
- `status`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Rules:
- unique branch code within tenant
- branch can override timezone
- branch can override currency
- if null, use tenant default

#### `tenant_roles`

Purpose:
- tenant-owned role definitions

Suggested fields:
- `id`
- `tenant_id`
- `name`
- `code`
- `is_system`
- `status`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

System role codes to seed:
- `tenant_owner`
- `tenant_admin`
- `branch_manager`
- `counsellor`
- `accounts`
- `operations`
- `placement`
- `faculty`
- `support_agent`

#### `privileges`

Purpose:
- platform-level capability catalog

Suggested fields:
- `id`
- `code`
- `name`
- `module`
- `description`
- `created_at`
- `updated_at`

#### `role_privileges`

Purpose:
- mapping between role and privilege

Suggested fields:
- `id`
- `role_id`
- `privilege_id`
- `created_at`

#### `users`

Purpose:
- tenant-owned users

Suggested fields:
- `id`
- `tenant_id`
- `role_id`
- `employee_code`
- `username`
- `email`
- `first_name`
- `last_name`
- `mobile_number`
- `whatsapp_number`
- `department`
- `designation`
- `password_hash`
- `is_active`
- `must_reset_password`
- `last_login_at`
- `last_login_ip`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Rules:
- unique email within tenant
- unique username within tenant
- no password reset or token clutter inside the main user table

#### `user_branches`

Purpose:
- many-to-many mapping between users and branches

Suggested fields:
- `id`
- `user_id`
- `branch_id`
- `is_primary`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

#### `user_hierarchy`

Purpose:
- manager/acting-manager relationships

Suggested fields:
- `id`
- `tenant_id`
- `user_id`
- `manager_user_id`
- `acting_manager_user_id`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Rules:
- hierarchy must remain inside same tenant

#### `tenant_settings`

Purpose:
- stable structured tenant settings

Suggested fields:
- `id`
- `tenant_id`
- `branding_name`
- `logo_path`
- `favicon_path`
- `default_timezone`
- `default_currency_code`
- `locale_code`
- `branch_visibility_mode`
- `enquiry_visibility_mode`
- `admission_visibility_mode`
- `created_at`
- `updated_at`

#### `tenant_setting_values`

Purpose:
- extensible key-value settings without overloading one giant settings table

Suggested fields:
- `id`
- `tenant_id`
- `key`
- `value`
- `value_type`
- `created_at`
- `updated_at`

#### `tenant_email_configs`

Purpose:
- SMTP config per tenant

Suggested fields:
- `id`
- `tenant_id`
- `provider_name`
- `from_name`
- `from_email`
- `host`
- `port`
- `username`
- `password_encrypted`
- `encryption`
- `is_default`
- `status`
- `created_at`
- `updated_at`

#### `tenant_whatsapp_configs`

Purpose:
- WhatsApp integration config per tenant

Suggested fields:
- `id`
- `tenant_id`
- `provider_name`
- `api_base_url`
- `api_key_encrypted`
- `sender_id`
- `is_default`
- `status`
- `created_at`
- `updated_at`

#### `password_reset_tokens`

Purpose:
- password reset lifecycle separate from users table

#### `user_password_histories`

Purpose:
- security and audit history

#### `audit_logs`

Purpose:
- consistent domain-level audit trail

### 4.4 Naming and standards

Use these conventions consistently:

- no `jbk_` prefixes
- singular technical entity names in code, plural table names in DB
- `id` as primary key
- `_id` foreign keys
- `created_at`, `updated_at`
- `created_by`, `updated_by`
- `is_active`, `is_default`, `is_system`
- `*_code` for machine-stable identifiers

### 4.5 Runtime services required

Build these shared services before module work:

- `TenantResolver`
- `BranchContextResolver`
- `PermissionService`
- `CurrentUserContext`
- `LocaleContextResolver`
- `TenantAccessPolicy`

Responsibilities:

- resolver layer determines current tenant and branch identity
- policy layer decides whether tenant status allows access
- resolve effective timezone
- resolve effective currency
- resolve effective privileges
- expose safe reusable context to downstream modules

### 4.6 Auth and session requirements

Phase 1A must include:

- login
- logout
- forgot password
- reset password
- session regeneration
- inactive-account blocking
- tenant-aware session storage
- branch-aware context in session

### 4.7 Admin shell scope

The admin shell must support:

- platform admin context
- tenant admin context
- branch-aware topbar
- privilege-aware menu rendering
- branch switcher for multi-branch users
- profile and session actions

### 4.8 Seeders

Provide seeders for:

- global privileges
- default system roles
- demo tenant
- demo branch
- demo tenant owner
- demo platform support user
- base tenant settings

### 4.9 Phase 1A acceptance criteria

Phase 1A is complete when:

- a tenant can be created
- branches can be created under that tenant
- roles can be created and managed
- privileges can be assigned to roles
- users can be created and assigned to multiple branches
- hierarchy mappings can be created
- tenant users can log in
- branch-aware access context works
- tenant SMTP config can be saved
- tenant WhatsApp config can be saved
- tenant and branch timezone and currency resolution works
- no tenant can access another tenant's records

---

## 5. Phase 1B - Billing, Plans, Entitlements, and Restriction Engine

### 5.1 Objectives

By the end of Phase 1B, the system must support:

- plan catalog
- monthly and yearly billing cycles
- feature entitlements by plan
- add-ons
- active user limits
- branch limits
- tenant-owned subscription control
- subscription status transitions
- grace period handling
- suspension restrictions
- tenant owner billing visibility
- plan-based feature gating

### 5.2 Billing model

Recommended v1 model:

- base plan
- optional add-ons
- capacity tiers
- monthly or yearly billing

This is a configurable commercial model without forcing every institute into the same package.

#### Base plan

Every subscription includes the CRM base foundation:

- enquiry capture and followup
- branch management
- user management
- basic dashboard and reports
- SMTP notification config

#### Capacity tiers

Primary monetization dimension:

- active user count
- branch count

Recommended structure:

- Starter
- Basic
- Growth
- Scale
- Enterprise

Rules:

- active users determine user tier enforcement
- branches are controlled only by `max_branches`
- do not create a separate `multi_branch` feature flag
- enterprise contracts may be manually managed by platform admin

#### Module add-ons

Recommended add-on module codes:

- `admissions`
- `service_tickets`
- `placement`
- `batch_management`
- `whatsapp`
- `advanced_reports`
- `student_portal`

Rules:

- modules can be enabled via plan or add-on
- adding modules mid-cycle is future commerce behavior, not required for 1B implementation
- removal policy can be renewal-based later

#### Billing cycles

Support from day one:

- monthly
- yearly

### 5.3 Subscription principles

- one tenant has one active subscription at a time
- roles do not override plan entitlements
- plan entitlements do not override missing privileges
- active-user counting is the first enforced limit
- yearly billing is supported from day one
- grace period is part of subscription state machine, not ad hoc logic

### 5.4 Billing entities

Introduce:

- FeatureCatalog
- Plan
- PlanPrice
- PlanFeature
- PlanLimit
- Subscription
- SubscriptionAddOn
- SubscriptionFeatureOverride
- BillingCustomer
- BillingInvoice
- BillingPayment
- BillingEvent

### 5.5 Recommended schema blueprint

#### `feature_catalog`

Purpose:
- master list of chargeable or gateable platform capabilities

Suggested fields:
- `id`
- `code`
- `name`
- `description`
- `category`
- `is_metered`
- `created_at`
- `updated_at`

Seed feature codes - module group codes:

- `crm_core`
- `admissions`
- `service_tickets`
- `placement`
- `batch_management`
- `whatsapp`
- `advanced_reports`
- `student_portal`

Seed limit codes - capacity controls:

- `max_users`
- `max_branches`
- `max_whatsapp_messages_per_month`

#### `plans`

Suggested fields:
- `id`
- `code`
- `name`
- `description`
- `status`
- `is_public`
- `created_at`
- `updated_at`

#### `plan_prices`

Suggested fields:
- `id`
- `plan_id`
- `billing_cycle`
- `currency_code`
- `price_amount`
- `billing_period_months`
- `status`
- `created_at`
- `updated_at`

#### `plan_features`

Purpose:
- feature entitlements only

Suggested fields:
- `id`
- `plan_id`
- `feature_code`
- `is_enabled`
- `created_at`
- `updated_at`

#### `plan_limits`

Purpose:
- capacity limits only

Suggested fields:
- `id`
- `plan_id`
- `limit_code`
- `limit_value`
- `created_at`
- `updated_at`

Rules:

- one row per limit code per plan
- `-1` means unlimited
- effective limit may be increased by add-ons later

#### `subscriptions`

Suggested fields:
- `id`
- `tenant_id`
- `plan_id`
- `billing_cycle`
- `status`
- `starts_at`
- `renews_at`
- `expires_at`
- `grace_ends_at`
- `cancelled_at`
- `trial_ends_at`
- `created_at`
- `updated_at`

#### `subscription_add_ons`

Suggested fields:
- `id`
- `subscription_id`
- `code`
- `name`
- `quantity`
- `unit_price_amount`
- `currency_code`
- `status`
- `starts_at`
- `ends_at`
- `created_at`
- `updated_at`

#### `subscription_feature_overrides`

Suggested fields:
- `id`
- `subscription_id`
- `feature_code`
- `is_enabled`
- `limit_type`
- `limit_value`
- `created_at`
- `updated_at`

#### `billing_customers`

Purpose:
- billing identity and invoicing metadata for tenant owner or legal entity

#### `billing_invoices`

Purpose:
- invoice records for billing lifecycle

#### `billing_payments`

Purpose:
- payment capture records

#### `billing_events`

Purpose:
- subscription state transitions and audit trail

### 5.6 Subscription state machine

Supported statuses:

- `trial`
- `active`
- `grace`
- `suspended`
- `cancelled`
- `expired`

Rules:

- `trial` becomes `active` on activation or payment
- `active` becomes `grace` after expiry if grace is enabled
- `grace` becomes `suspended` after grace end
- `cancelled` means no renewal, but service may continue until term end
- `expired` means no active service period remains

Default grace recommendation:
- 7 days

### 5.7 Restriction model

Do not fully lock the system immediately on expiry.

#### Active
- access allowed according to plan and privilege

#### Grace
- access still allowed
- owner and admin warning banners
- billing reminders visible

#### Suspended
- owner and tenant admin may access billing and support surfaces
- standard operational users blocked
- create and update business operations blocked

This must be enforced server-side.

### 5.8 Entitlement enforcement

Feature availability is decided by:

`subscription status` + `plan/add-on entitlements` + `user privilege`

Examples:

- no placement entitlement -> placement hidden and blocked for tenant
- entitlement exists but user lacks privilege -> blocked for user
- suspended tenant -> normal operations blocked regardless of privilege

### 5.9 Usage limits

V1 enforced limits:

- active user count
- branch count

Counting rules:

- active users count toward plan limit
- inactive users do not count
- branches are counted per tenant

### 5.10 Billing UI scope

Platform admin needs:

- plan management
- plan pricing management
- feature entitlement management
- plan limit management
- subscription management
- billing event visibility

Tenant owner needs:

- current plan visibility
- renewal and expiry visibility
- grace period warnings
- enabled module summary
- active user usage summary
- branch usage summary
- add-on summary
- invoice history placeholder

### 5.11 Runtime services required

Build:

- `SubscriptionPolicyService`
- `FeatureGateService`
- `UsageLimitService`

Responsibilities:

- decide if feature is enabled for tenant
- decide if current action should be blocked
- decide if tenant is over limit
- expose billing status to UI and route guards

### 5.12 Phase 1B acceptance criteria

Phase 1B is complete when:

- plans can be created
- monthly and yearly pricing can be defined
- features can be assigned to plans
- limits can be assigned to plans
- subscriptions can be attached to tenants
- add-ons can be applied structurally
- tenant feature access is calculated correctly
- tenant limit access is calculated correctly
- grace period transition works
- suspension restriction works
- owner and tenant admin retain billing access during suspension
- blocked modules are hidden from menus
- blocked module routes are denied server-side

---

## 6. Implementation Order

### Phase 1A implementation order

1. migrations for tenant foundation tables
2. seeders for privileges, roles, demo tenant, demo branch, demo owner
3. tenant-aware models and repositories
4. auth and session foundation
5. tenant, branch, locale context services
6. role and privilege management
7. user, branch, hierarchy management
8. admin shell
9. tenant config (SMTP, WhatsApp, settings)

### Phase 1B implementation order

1. billing catalog schema
2. plan and feature seed data
3. subscription state machine
4. entitlement resolution service
5. usage limit service
6. suspension and grace enforcement
7. billing UI for platform admin
8. billing summary for tenant owner
9. final route and menu gating

---

## 7. Cross-Cutting Standards

### 7.1 Security

- encrypted storage for external provider secrets
- password reset tokens stored separately
- session regeneration on login
- no credential blobs mixed into generic settings

### 7.2 Auditability

- actor fields on all mutable records
- domain audit log for sensitive changes
- billing event log for subscription changes

### 7.3 Performance

- tenant and branch indexes from day one
- code paths must always scope by tenant
- menus and entitlement checks should use cached resolved context where safe

### 7.4 Scalability

- no tenant-specific branching in core code
- no hardcoded institute assumptions
- no reliance on numeric role constants
- no global branch or global settings assumptions

### 7.5 Product maintainability

- structured config tables
- reusable policy services
- clean boundaries between entitlement and authorization
- avoid copying legacy `jbkcrm` naming and mixed patterns

---

## 8. Known Mapping From `jbkcrm`

Reference-only source tables:

- `jbk_branches`
- `jbk_privileges`
- `jbk_roles`
- `jbk_roles_privileges`
- `jbk_settings`
- `jbk_settings_meta`
- `jbk_users`
- `jbk_user_branch`
- `jbk_user_head`
- `jbk_whatsapp_logs`

Mapping direction:

- `jbk_branches` -> `tenant_branches`
- `jbk_roles` -> `tenant_roles`
- `jbk_roles_privileges` -> `role_privileges`
- `jbk_users` -> `users`
- `jbk_user_branch` -> `user_branches`
- `jbk_user_head` -> `user_hierarchy`
- `jbk_settings` + `jbk_settings_meta` -> `tenant_settings` + `tenant_setting_values`
- WhatsApp config and logging to dedicated tenant-specific integration tables

The intent is domain continuity with better SaaS structure.

---

## 9. Risks To Avoid

Do not allow these anti-patterns into implementation:

- giant all-purpose settings table
- tenant-unaware queries
- module access based only on role
- billing access based only on subscription without role consideration
- global branches
- global tenant-shared roles
- India-only timezone and currency assumptions
- raw copied schema from `jbkcrm`

---

## 10. Test Strategy

### 10.1 Scope

Automated tests are required from the start for service layer, model scoping, auth, subscription state machine, entitlement logic, and usage limit logic.

UI automation is not part of Phase 1A or 1B.

### 10.2 Must-test areas

- tenant isolation
- branch isolation
- role and privilege resolution
- auth flows
- tenant status policy enforcement
- timezone and currency resolution
- subscription state transitions
- entitlement checks
- active-user and branch limit checks
- suspension behavior

### 10.3 Key behavior rules to test

- resolver layer resolves identity and context only
- policy layer decides whether `draft`, `suspended`, `cancelled`, or `expired` tenants may access the requested surface
- platform admin may manage tenants from platform surfaces
- tenant operations still respect entitlements unless explicit support impersonation exists

---

## 11. Recommendation For Next Step

Immediate execution order from current repo state:

1. verify `.env` database settings on local and droplet
2. run migrations and seeders in the target environment
3. validate login with seeded tenant owner credentials
4. confirm protected `/dashboard` flow through `auth + tenant + suspension`
5. build Admin shell
6. build User management CRUD
7. build Branch management CRUD
8. build Role management CRUD
9. complete tenant settings UI for SMTP and WhatsApp
10. begin Phase 1B billing catalog and policy services

Only after these foundation items are stable should enquiry, admissions, service, placement, and student portal module work begin.

