# EDCRM SaaS Phase 1A / 1B / 1C Product Architecture Plan

## 1. Executive Summary

This document defines the formal execution plan for the first three platform phases of `edcrm-saas`.

- **Phase 1A** establishes the multi-tenant SaaS foundation
- **Phase 1B** establishes billing catalog, entitlements, limits, and subscription enforcement engine
- **Phase 1C** establishes subscription commerce — self-service signup, module marketplace, payment gateway, invoicing, and automated renewal/suspension pipeline

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
- repositories/services must be tenant-aware
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

### 3.4 Global standards

The platform must not assume India-only usage.

Global standards to support from the foundation:

- tenant default timezone
- tenant default currency
- tenant locale
- branch timezone override
- branch currency override
- globally safe naming conventions
- future internationalization readiness

### 3.5 Configuration strategy

Do not copy `jbkcrm`’s giant `settings` table model.

Instead:

- use structured settings for stable configuration
- use key-value extension table for flexible tenant-specific config
- keep SMTP and WhatsApp in dedicated tables

---

## 4. Phase 1A — Multi-Tenant Foundation

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

Responsibilities:

- determine current tenant
- determine current active branch
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
- tenant/branch timezone and currency resolution works
- no tenant can access another tenant’s records

---

## 5. Phase 1B — Billing, Plans, Entitlements, and Restriction Engine

### 5.1 Objectives

By the end of Phase 1B, the system must support:

- plan catalog
- monthly and yearly billing cycles
- feature entitlements by plan
- add-ons
- active user limits
- branch-aware but tenant-owned subscription control
- subscription status transitions
- grace period handling
- suspension restrictions
- tenant owner billing visibility
- plan-based feature gating

### 5.2 Billing model

Recommended v1 model: **base plan + module add-ons + capacity tiers + billing cycle discount**

This is a module marketplace model, not a preset bundle model. It lets each institute pay only for what they need and naturally upsells as they grow.

#### Base Plan (always included — the CRM foundation)

Every subscription includes the following at the base price:

- Enquiry capture and followup (core CRM engine)
- Branch management
- User management
- Basic dashboard and reports
- SMTP notification config

The base plan is not sold without a capacity tier.

#### Capacity Tiers (user count — the primary revenue driver)

| Tier | Active Users | Branches |
|------|-------------|----------|
| Starter | up to 5 | 1 |
| Basic | 6–10 | 2 |
| Growth | 11–25 | 5 |
| Scale | 26–50 | 10 |
| Enterprise | 50+ | unlimited (custom contract) |

Rules:
- Active user count determines tier
- When user count crosses a tier boundary, tenant is prompted to upgrade
- Soft block on adding new users when at tier limit (no hard system lockout)
- Enterprise tier is not self-service — platform admin creates these manually

#### Module Add-ons (tenant picks only what they need)

| Module Code | What it covers |
|------------|---------------|
| `admissions` | Admissions conversion, fee structure, installments, payments |
| `service_tickets` | Support ticket / service request workflow |
| `placement` | Placement workflow, jobs, mock interviews, college connect |
| `batch_management` | Batch / course management, faculty, attendance |
| `whatsapp` | WhatsApp integration (provider config + messaging) |
| `advanced_reports` | Full reporting suite beyond basic dashboard |
| `student_portal` | Student self-service portal (Phase 4+) |

Rules:
- Modules can be added or removed at subscription renewal
- Adding a module mid-cycle is prorated from today to the next billing date
- Removing a module takes effect at next renewal (data retained, access gated)

#### Bundle Deal

A tenant that subscribes to all available modules in one go receives a discount (recommended: 20% off a la carte total). Platform admin configures bundle pricing.

#### Billing Cycles

| Cycle | Pricing |
|-------|---------|
| Monthly | Standard rate |
| Yearly | 15–20% discount (encourages annual commitment and reduces churn) |

#### Example pricing combinations

- Small counselling centre: Starter + Admissions → low entry barrier
- Growing institute: Growth + Admissions + Placement + WhatsApp → natural upsell
- Large multi-branch institute: Scale + All Modules → bundle deal applies
- Enterprise: custom contract, platform admin managed

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

Seed feature codes — module group codes (what is sold and gated):

- `crm_core` — enquiry capture + followup (included in all base plans, not sold separately)
- `admissions` — admissions conversion, fee structure, installments, payments
- `service_tickets` — ticket and support workflow
- `placement` — placement workflow, jobs, mock interviews, college connect
- `batch_management` — batch/course management, faculty, attendance
- `whatsapp` — WhatsApp integration (provider config + outbound messaging)
- `advanced_reports` — full reporting suite beyond base dashboard
- `student_portal` — student self-service portal (future)

Seed feature codes — capacity codes (limits, not module flags):

- `max_users` — maximum active users allowed
- `max_branches` — maximum branches allowed
- `max_whatsapp_messages_per_month` — future metered billing readiness

Note: capacity codes are stored in `plan_limits`, not `plan_features`. Feature codes are stored in `plan_features`.

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
- which module features are included in a plan (feature entitlement, not capacity)

Suggested fields:
- `id`
- `plan_id`
- `feature_code`
- `is_enabled`
- `created_at`
- `updated_at`

Note: capacity limits (max users, max branches) are in `plan_limits`, not here. Do not mix entitlements with limits.

#### `plan_limits`

Purpose:
- capacity limits per plan, separate from feature entitlements

Suggested fields:
- `id`
- `plan_id`
- `limit_code` — `max_users`, `max_branches`, `max_whatsapp_messages_per_month`
- `limit_value` — numeric
- `created_at`
- `updated_at`

Rules:
- one row per limit_code per plan
- limit_value of `-1` means unlimited
- limits are additive with add-ons (e.g. Growth plan has max_users=25, add-on grants +10 → effective limit=35)

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
- billing identity and invoicing metadata for tenant owner/legal entity

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

- `trial` becomes `active` on activation/payment
- `active` becomes `grace` after expiry if grace is enabled
- `grace` becomes `suspended` after grace end
- `cancelled` means no renewal, but service may continue until term end
- `expired` means no active service period remains

Default grace recommendation:
- 7 days

### 5.7 Restriction model

Do not fully lock the system immediately on expiry. Use a graduated restriction model.

#### Day-by-day restriction schedule

| Day | State | Restriction applied |
|-----|-------|-------------------|
| Expiry day | Grace begins | Warning banner for owner and admin only. All operations continue normally |
| Grace day 1–3 | Grace | Daily warning banner escalates in urgency. No operational restriction |
| Grace day 4–7 | Grace (late) | Warning banners for all users. Billing nag shown on every page for owner |
| Grace day 8 | Suspended | Operational staff enter read-only mode — can view records, cannot create/edit/delete |
| Suspended | — | Owner retains access to billing, support, and data export surfaces only |
| Cancelled (30 days post) | Cancelled | Data export window. Owner can download data |
| Cancelled (30+ days) | Expired | Data deletion scheduled per retention policy |

#### State-by-state detail

**Active**
- access allowed according to plan entitlement + user privilege

**Grace**
- access still allowed for all users
- owner and admin see billing warning banner on every page
- warning severity escalates daily (yellow → orange → red)

**Suspended**
- operational users (counsellor, accounts, operations, placement, faculty) are in read-only mode
- no new enquiries, admissions, fees, or tickets can be created
- existing records can be viewed
- owner and tenant_admin retain access to billing and support pages
- platform admin retains full access

**Cancelled**
- 30-day data export window begins
- all operational users blocked
- owner can download full data export
- platform admin can extend this window manually

**Expired / Data deleted**
- no access
- data deleted per retention policy

This must be enforced server-side on every request, not only in the UI menu.

### 5.8 Entitlement enforcement

Feature availability must be decided by:

`subscription status` + `plan/add-on entitlements` + `user privilege`

Examples:

- no placement entitlement -> placement module hidden and blocked for all users in tenant
- entitlement exists but user lacks privilege -> blocked for that user
- suspended tenant -> normal operations blocked regardless of privilege

### 5.9 Usage limits

V1 enforced limits:

- **active user count** (primary limit — blocks new user creation at tier ceiling)
- **branch count** (secondary limit — blocks new branch creation at tier ceiling)

Schema is also ready for future limits:

- whatsapp messages per month (metered billing, Phase 4+)
- enquiry volume limits (for future lower-tier restrictions)

Counting rules:
- active users (`is_active = 1`) count toward `max_users` limit
- inactive/deactivated users do not count
- branches with `status = active` count toward `max_branches` limit
- add-on quantities are additive to the plan base limit (e.g. Growth plan `max_users=25` + extra-users add-on `quantity=10` → effective limit = 35)

Enforcement behavior:
- **Soft block at 100%**: tenant admin sees warning, can view but cannot add more users/branches
- **No hard lockout for existing data**: existing users and branches are never auto-deactivated by a limit change

### 5.10 Billing UI scope

Platform admin needs:

- plan management (create/edit plans, set capacity tiers)
- plan pricing management (monthly/yearly prices per currency)
- feature entitlement assignment per plan
- capacity limit assignment per plan
- subscription management (view all tenant subscriptions, override, extend)
- billing event visibility and audit
- manual payment recording for enterprise/bank transfer customers
- revenue summary dashboard (MRR, ARR, churn)

Tenant owner needs:

- current plan and capacity tier visibility
- module add-ons enabled summary
- active user count vs limit ("12 of 25 users")
- branch count vs limit ("3 of 5 branches")
- renewal/expiry date and next billing amount
- grace period warning banners
- upgrade prompt when approaching limits
- invoice history (download PDF)
- add-on management (add a module, request upgrade)

Note: full self-service checkout (payment gateway, plan selection flow) is Phase 1C scope, not Phase 1B.

### 5.11 Runtime services required

Build:

- `SubscriptionPolicyService` — resolve current subscription state, grace window, suspension status
- `FeatureGateService` — decide if a module feature is enabled for the current tenant
- `UsageLimitService` — check current usage vs plan limits (users, branches)

Responsibilities:

- decide if feature is enabled for tenant (plan entitlement + add-on overrides)
- decide if current action should be blocked (suspension state)
- decide if tenant is over user/branch limit
- expose billing status and warning level to UI and route guards
- return structured gate result: `allowed | blocked | warning | read_only`

### 5.12 Tenant onboarding flows

Two distinct onboarding paths must be supported:

#### Flow A — Platform admin creates tenant (v1 default, enterprise)

1. Platform admin creates tenant record with plan and modules assigned
2. Sets trial start date and trial duration
3. Tenant owner receives welcome email with login credentials
4. Owner logs in, completes company profile, configures SMTP/WhatsApp
5. Trial period begins

This is the only required flow for Phase 1A/1B. Implement this first.

#### Flow B — Self-service signup (Phase 1C)

1. Prospect visits public pricing page
2. Selects capacity tier + module add-ons
3. Fills company info (institute name, owner name, email, phone)
4. Trial starts automatically (14 days, no payment required)
5. Trial nearing end → payment prompt via payment gateway
6. Payment captured → subscription activates

Flow B is Phase 1C scope. The schema designed in Phase 1B must support both flows from day one.

### 5.13 Subscription lifecycle — upgrade, downgrade, module changes

These policies must be defined before Phase 1C implementation:

| Scenario | Policy |
|---------|--------|
| Upgrade capacity tier mid-cycle | Prorate remaining days, charge difference immediately |
| Add a module mid-cycle | Prorate from today to next billing date, charge immediately |
| Downgrade capacity tier | Apply at next renewal date. No mid-cycle downgrade |
| Remove a module | Apply at next renewal. Data retained, access gated immediately after renewal |
| User count exceeds current tier | Soft block on adding new users. Banner prompts upgrade. Existing users unaffected |
| Yearly to monthly switch | Apply at next renewal. No mid-cycle billing cycle change |
| Monthly to yearly upgrade | Apply immediately. Credit remaining monthly days as prorated discount |
| Cancel subscription | Subscription remains active until period end. Grace/suspension does not apply. Data retention window begins after period end |

### 5.14 Payment gateway

Phase 1C requires a payment gateway. Recommended selection:

**Primary: Razorpay**
- INR support, UPI, auto-debit mandates for recurring billing
- Subscription object model maps to our subscription state machine
- Good India-first developer experience

**Secondary: Stripe (future)**
- Required if international customers need credit card billing
- Design payment abstraction layer so gateway is swappable

**Manual payment support (always required):**
- Platform admin can record a manual payment (bank transfer, cheque)
- Manual payment advances the subscription state same as gateway payment
- Required for enterprise customers who pay via invoice

### 5.15 Phase 1B acceptance criteria

Phase 1B is complete when:

- plans can be created with capacity tiers
- module features can be assigned to plans
- capacity limits (max_users, max_branches) can be defined per plan
- monthly and yearly pricing can be defined per plan per currency
- subscriptions can be attached to tenants by platform admin
- module add-ons can be applied to a subscription
- tenant module access is calculated correctly from plan + add-ons
- active user count vs limit is calculated correctly
- branch count vs limit is calculated correctly
- grace period transition triggers automatically (cron or event)
- day-by-day restriction levels are enforced correctly
- suspension read-only enforcement works server-side
- owner and tenant_admin retain billing access during suspension
- blocked modules are hidden from menus and denied server-side
- tenant owner billing summary page is functional
- platform admin billing management pages are functional

---

## 6. Phase 1C — Subscription Commerce

### 6.1 Objectives

By the end of Phase 1C the system must support:

- public pricing page
- self-service tenant signup with trial (Flow B from section 5.12)
- module marketplace UI for tenant owners
- payment gateway integration (Razorpay)
- invoice PDF generation
- upgrade / module add flow with proration
- automated renewal, payment failure, grace, and suspension pipeline
- yearly vs monthly plan switch with discount display
- usage dashboard (users and branches vs limit)
- platform admin revenue and churn dashboard

### 6.2 Phase 1C scope items

| # | Item |
|---|------|
| 1 | Public pricing page — capacity tiers + module add-ons, monthly/yearly toggle |
| 2 | Self-service signup form — institute name, owner details, tier + module selection |
| 3 | Trial-to-paid conversion flow — reminder emails + payment prompt |
| 4 | Module marketplace UI — tenant sees enabled modules and can request/purchase more |
| 5 | Capacity usage widget — "12 of 25 users used. Upgrade for more." |
| 6 | Razorpay integration — subscription object, auto-debit mandate |
| 7 | Invoice PDF generation on each billing event |
| 8 | Proration engine — mid-cycle upgrade and module add cost calculation |
| 9 | Plan upgrade / downgrade flows |
| 10 | Yearly ↔ monthly switch flows |
| 11 | Manual payment recording (platform admin) |
| 12 | Automated cron pipeline: payment failure → grace → suspension → expiry |
| 13 | Platform admin revenue dashboard (MRR, ARR, active subscriptions, churn list) |
| 14 | Data export flow for cancelled tenants |

### 6.3 Phase 1C dependencies

Phase 1C depends on Phase 1B being fully stable:

- subscription state machine must work
- entitlement resolution must work
- grace/suspension enforcement must work
- billing entities schema must be in production

### 6.4 Phase 1C acceptance criteria

Phase 1C is complete when:

- a new institute can sign up without platform admin intervention
- trial starts automatically and reminder emails fire at day 7 and day 12
- payment is captured via Razorpay and subscription activates
- a tenant can add a module and be charged the prorated amount
- invoices are generated and downloadable as PDF
- payment failure triggers grace period automatically
- grace expiry triggers suspension automatically
- cancelled tenant receives 30-day data export window
- platform admin can see MRR, active tenants, and churn in a dashboard

---

## 7. Implementation Order

### Phase 1A implementation order

1. migrations for tenant foundation tables
2. seeders for privileges, roles, demo tenant, demo branch, demo owner
3. tenant-aware models and repositories
4. auth and session foundation
5. tenant/branch/locale context services
6. role/privilege management
7. user + branch + hierarchy management
8. admin shell
9. tenant config (SMTP/WhatsApp/settings)

### Phase 1B implementation order

1. billing catalog schema (feature_catalog, plans, plan_prices, plan_features, plan_limits)
2. plan and feature seed data (module codes, capacity tier plans, monthly/yearly prices)
3. subscription state machine (trial → active → grace → suspended → cancelled → expired)
4. entitlement resolution service (FeatureGateService)
5. usage limit service (UsageLimitService)
6. suspension/grace enforcement (SubscriptionPolicyService, server-side middleware)
7. billing UI for platform admin (plan management, subscription assignment)
8. billing summary for tenant owner (plan, modules, usage, renewal)
9. final route and menu gating (entitlement + suspension enforcement)

### Phase 1C implementation order

1. public pricing page (static with monthly/yearly toggle)
2. self-service signup flow and trial activation
3. Razorpay integration (subscription object + webhook handler)
4. proration engine
5. module marketplace UI for tenant owners
6. invoice PDF generation
7. upgrade/downgrade/module-change flows
8. automated cron pipeline (renewal, failure, grace, suspension)
9. trial reminder email pipeline
10. platform admin revenue dashboard
11. data export flow for cancelled tenants

---

## 8. Cross-Cutting Standards

### 8.1 Security

- encrypted storage for external provider secrets
- password reset tokens stored separately
- session regeneration on login
- no credential blobs mixed into generic settings

### 8.2 Auditability

- actor fields on all mutable records
- domain audit log for sensitive changes
- billing event log for subscription changes

### 8.3 Performance

- tenant and branch indexes from day one
- code paths must always scope by tenant
- menus and entitlement checks should use cached resolved context where safe

### 8.4 Scalability

- no tenant-specific branching in core code
- no hardcoded institute assumptions
- no reliance on numeric role constants
- no global branch or global settings assumptions

### 8.5 Product maintainability

- structured config tables
- reusable policy services
- clean boundaries between entitlement and authorization
- avoid copying legacy `jbkcrm` naming and mixed patterns

---

## 9. Known Mapping From `jbkcrm`

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
- WhatsApp config/logging to dedicated tenant-specific integration tables

The intent is domain continuity with better SaaS structure.

---

## 10. Risks To Avoid

Do not allow these anti-patterns into implementation:

- giant all-purpose settings table
- tenant-unaware queries
- module access based only on role
- billing access based only on subscription without role consideration
- global branches
- global tenant-shared roles
- India-only timezone/currency assumptions
- raw copied schema from `jbkcrm`

---

## 11. Overall Phase Roadmap

```
Phase 1A  — Multi-tenant foundation, users, branches, roles, auth, SMTP/WA config
Phase 1B  — Billing catalog, module entitlements, capacity limits, subscription engine, gates
Phase 1C  — Self-service signup, module marketplace, Razorpay, invoicing, automation pipeline
Phase 2   — Core modules: Enquiry → Followup → Admission → Fees
Phase 3   — Placement, Service Tickets, Batch / Course Management
Phase 4   — WhatsApp messaging, Advanced Reports, Student Portal
```

## 12. Test Strategy

### 12.1 Approach

Automated service layer tests are written alongside development from Phase 1A onward. No UI testing in Phase 1. UI/browser automation is deferred to a later phase when screens stabilise.

Framework: **PHPUnit** (already available in CodeIgniter 4 via `phpunit.xml.dist`).

### 12.2 What is tested and what is not

| Layer | Automated now | Deferred |
|-------|--------------|---------|
| Service layer (resolvers, gates, policy services) | Yes — Phase 1A/1B/1C | — |
| Model query scoping (tenant isolation) | Yes — Phase 1A | — |
| Auth flows (login, logout, session, password reset) | Yes — Phase 1A | — |
| Subscription state machine | Yes — Phase 1B | — |
| Entitlement and gate logic | Yes — Phase 1B | — |
| Proration and billing calculations | Yes — Phase 1C | — |
| Payment webhook handling | Yes — Phase 1C | — |
| Invoice generation logic | Yes — Phase 1C | — |
| Controller responses / HTTP layer | Minimal (happy-path only) | Full coverage later |
| UI, forms, views, JavaScript | No | Phase 4+ |
| Report outputs and PDF rendering | No | Phase 4+ |
| Email content and template rendering | No | Phase 4+ |

### 12.3 Test naming convention

```
{ServiceName}Test.php
  test_{scenario}_{expectedOutcome}

Example:
  FeatureGateServiceTest.php
    test_moduleEnabled_returnsAllowed()
    test_moduleDisabled_returnsBlocked()
    test_suspendedTenant_returnsBlocked()
```

### 12.4 CI integration

Run full test suite on every commit. A failing test blocks merge. No exceptions.

---

## 13. Automated Test Cases — Phase 1

---

### 13.1 TenantResolver

| ID | Scenario | Expected |
|----|---------|---------|
| TR-01 | Valid tenant slug in request → resolve tenant | Returns correct Tenant object |
| TR-02 | Unknown slug in request | Throws TenantNotFoundException |
| TR-03 | Tenant with status `suspended` | Returns tenant (resolver does not block — policy layer does) |
| TR-04 | Tenant with status `cancelled` | Returns tenant with cancelled flag |
| TR-05 | Tenant with status `draft` (not yet active) | Returns tenant — caller must check status |
| TR-06 | Empty slug | Throws TenantNotFoundException |

---

### 13.2 BranchContextResolver

| ID | Scenario | Expected |
|----|---------|---------|
| BC-01 | User assigned to branch, branch is active | Returns correct Branch object |
| BC-02 | User not assigned to requested branch | Throws BranchAccessDeniedException |
| BC-03 | Branch has timezone set | Returns branch timezone |
| BC-04 | Branch has no timezone set | Falls back to tenant default timezone |
| BC-05 | Branch has currency set | Returns branch currency |
| BC-06 | Branch has no currency set | Falls back to tenant default currency |
| BC-07 | User assigned to multiple branches, switches branch | Session updates to new branch context |
| BC-08 | User switches to branch from different tenant | Throws BranchAccessDeniedException |

---

### 13.3 Tenant Isolation (critical)

| ID | Scenario | Expected |
|----|---------|---------|
| TI-01 | Query branches scoped to Tenant A | Returns only Tenant A's branches |
| TI-02 | Query branches scoped to Tenant B | Returns only Tenant B's branches, never Tenant A's |
| TI-03 | Direct branch ID lookup — branch belongs to Tenant B, request from Tenant A | Returns null / throws NotFoundException |
| TI-04 | Direct user ID lookup — user belongs to Tenant B, request from Tenant A | Returns null / throws NotFoundException |
| TI-05 | Role created under Tenant A | Not visible to Tenant B |
| TI-06 | Model base scope always injects tenant_id | SQL WHERE clause contains tenant_id |
| TI-07 | Bypass attempt: raw query without tenant scope | Should not exist — enforced at repository base level |

---

### 13.4 PermissionService

| ID | Scenario | Expected |
|----|---------|---------|
| PM-01 | User's role has privilege X assigned | `hasPrivilege(user, 'X')` returns true |
| PM-02 | User's role does not have privilege X | `hasPrivilege(user, 'X')` returns false |
| PM-03 | Platform admin user | Bypasses all privilege checks, returns true for any check |
| PM-04 | Role has no privileges assigned | All privilege checks return false |
| PM-05 | Privilege from different tenant's role | Not visible, returns false |
| PM-06 | Inactive user attempts privilege check | Returns false (inactive users have no effective permissions) |

---

### 13.5 Auth Flow

| ID | Scenario | Expected |
|----|---------|---------|
| AF-01 | Correct email + password for active user | Login succeeds, session created with tenant_id and branch_id |
| AF-02 | Correct email + wrong password | Login fails, error returned |
| AF-03 | Correct email + password but user is inactive | Login blocked with inactive-account message |
| AF-04 | Login as user from Tenant A via Tenant B's subdomain | Login blocked — tenant mismatch |
| AF-05 | Logout | Session destroyed, redirect to login |
| AF-06 | Session contains tenant_id | Verified on every request by middleware |
| AF-07 | Session contains branch_id | Verified on first branch-aware request |
| AF-08 | Session regeneration on login | Session ID changes after successful login |
| AF-09 | Forgot password — valid email | Token generated, stored in password_reset_tokens, email sent |
| AF-10 | Forgot password — unknown email | No error disclosed, silent success response |
| AF-11 | Password reset — valid token, not expired | Password updated, token marked used |
| AF-12 | Password reset — expired token | Reset blocked with expired message |
| AF-13 | Password reset — already-used token | Reset blocked with invalid-token message |
| AF-14 | Password reset — token belongs to different tenant | Reset blocked |
| AF-15 | must_reset_password flag set on user | User is forced to reset password before accessing any page |

---

### 13.6 User and Branch Assignment

| ID | Scenario | Expected |
|----|---------|---------|
| UB-01 | Create user under Tenant A | User has tenant_id = Tenant A |
| UB-02 | Assign user to branch | user_branches row created with is_primary flag |
| UB-03 | Assign user to branch from different tenant | Blocked |
| UB-04 | User with multiple branches | Returns list of assigned branches correctly |
| UB-05 | Remove user from branch | user_branches row soft-deleted or removed |
| UB-06 | Set primary branch | is_primary = 1 for one branch only, others set to 0 |

---

### 13.7 User Hierarchy

| ID | Scenario | Expected |
|----|---------|---------|
| UH-01 | Create manager relationship within same tenant | Hierarchy created |
| UH-02 | Create manager relationship across tenants | Blocked — tenant_id mismatch |
| UH-03 | Assign manager and acting manager | Both stored correctly |
| UH-04 | Remove manager relationship | Row deleted or nulled |

---

### 13.8 LocaleContextResolver

| ID | Scenario | Expected |
|----|---------|---------|
| LC-01 | Tenant default timezone = Asia/Kolkata, branch has no override | Resolved timezone = Asia/Kolkata |
| LC-02 | Tenant default timezone = Asia/Kolkata, branch timezone = America/New_York | Resolved timezone = America/New_York |
| LC-03 | Tenant default currency = INR, branch has no override | Resolved currency = INR |
| LC-04 | Tenant default currency = INR, branch currency = USD | Resolved currency = USD |

---

### 13.9 SubscriptionPolicyService

| ID | Scenario | Expected |
|----|---------|---------|
| SP-01 | Subscription status = trial, trial_ends_at in future | Returns `trial`, no restriction |
| SP-02 | Subscription status = trial, trial_ends_at in past, no payment | Returns `grace` (trial expired) |
| SP-03 | Subscription status = active, expires_at in future | Returns `active`, no restriction |
| SP-04 | Subscription status = active, expires_at = today | Returns `grace`, grace_ends_at set |
| SP-05 | Subscription status = grace, grace_ends_at in future (day 1–7) | Returns `grace`, access allowed |
| SP-06 | Subscription status = grace, grace_ends_at in past (day 8+) | Transitions to `suspended` |
| SP-07 | Subscription status = suspended | Returns `suspended`, write operations blocked |
| SP-08 | Subscription status = cancelled, within term end | Returns `cancelled`, read-only access |
| SP-09 | Subscription status = expired | Returns `expired`, full block |
| SP-10 | Grace ends at calculation = expires_at + 7 days | Correct date computed |
| SP-11 | Payment received during grace | Status transitions to `active`, grace cleared |
| SP-12 | No subscription row exists for tenant | Treated as `expired` |

---

### 13.10 FeatureGateService

| ID | Scenario | Expected |
|----|---------|---------|
| FG-01 | Module `admissions` enabled in plan, subscription active | Gate returns `allowed` |
| FG-02 | Module `admissions` not in plan, subscription active | Gate returns `blocked` |
| FG-03 | Module `placement` not in base plan but enabled via add-on | Gate returns `allowed` |
| FG-04 | Module `placement` in base plan but disabled via subscription_feature_override | Gate returns `blocked` |
| FG-05 | Any module, subscription = suspended | Gate returns `blocked` regardless of plan |
| FG-06 | Any module, subscription = expired | Gate returns `blocked` |
| FG-07 | Module `crm_core`, subscription active | Always `allowed` — cannot be disabled |
| FG-08 | Platform admin checks any module gate | Always `allowed` — bypasses entitlement |
| FG-09 | Tenant owner checks billing gate during suspension | Returns `allowed` — owner retains billing access |
| FG-10 | Operational user checks any module during suspension | Returns `blocked` |
| FG-11 | Entitlement allowed but user lacks privilege | PermissionService blocks — gate is not the only check |

---

### 13.11 UsageLimitService

| ID | Scenario | Expected |
|----|---------|---------|
| UL-01 | Active user count = 10, plan max_users = 25 | Returns `under_limit`, new user allowed |
| UL-02 | Active user count = 25, plan max_users = 25 | Returns `at_limit`, new user blocked |
| UL-03 | Active user count = 26, plan max_users = 25 | Returns `over_limit` (data integrity guard) |
| UL-04 | Inactive users not counted in active user count | Count excludes is_active = 0 rows |
| UL-05 | Add-on `extra_users` quantity = 10, base max_users = 25 | Effective limit = 35 |
| UL-06 | Plan max_users = -1 (unlimited) | Always returns `allowed`, no cap |
| UL-07 | Branch count = 3, plan max_branches = 5 | Returns `under_limit`, new branch allowed |
| UL-08 | Branch count = 5, plan max_branches = 5 | Returns `at_limit`, new branch blocked |
| UL-09 | Plan max_branches = -1 (unlimited) | Always returns `allowed` |
| UL-10 | Deactivating a user reduces active count | Count decrements after is_active set to 0 |

---

### 13.12 Phase 1C — Proration Engine

| ID | Scenario | Expected |
|----|---------|---------|
| PE-01 | Add module, 30 days remaining in 30-day month | Full module month price charged |
| PE-02 | Add module, 15 days remaining in 30-day month | 50% of module month price charged |
| PE-03 | Add module, 1 day remaining | 1/30 of module month price charged |
| PE-04 | Add module, 0 days remaining (renewal day) | Nothing charged — next cycle billing handles it |
| PE-05 | Upgrade capacity tier, 15 days remaining | Difference between tiers × (15/30) charged |
| PE-06 | Yearly billing, add module mid-year | Prorate over remaining days in 365-day year |
| PE-07 | Proration amount rounded to 2 decimal places | No floating point errors in final charge |

---

### 13.13 Phase 1C — Razorpay Webhook Handler

| ID | Scenario | Expected |
|----|---------|---------|
| RZ-01 | `payment.captured` event, valid signature | Subscription status → active, billing_payment record created |
| RZ-02 | `payment.failed` event, valid signature | Subscription grace period triggered, billing_event logged |
| RZ-03 | `subscription.charged` event | renews_at and expires_at extended, invoice created |
| RZ-04 | `subscription.cancelled` event | Subscription status → cancelled, billing_event logged |
| RZ-05 | Invalid webhook signature | Request rejected with 400, not processed |
| RZ-06 | Duplicate webhook (same payment_id received twice) | Idempotent — second event ignored, no duplicate payment record |
| RZ-07 | Webhook for unknown tenant subscription | Logged as anomaly, not processed |

---

### 13.14 Phase 1C — Invoice Generation

| ID | Scenario | Expected |
|----|---------|---------|
| IV-01 | Subscription activated | Invoice created with correct plan + module line items |
| IV-02 | Subscription renewed | New invoice created for renewal period |
| IV-03 | Module added mid-cycle | Invoice created for prorated amount |
| IV-04 | Invoice amount = plan price + add-on prices | Line item totals match header total |
| IV-05 | Invoice number | Sequential per tenant (e.g. INV-0001, INV-0002) |
| IV-06 | Invoice for yearly billing | Correct annual amount, billing_period_months = 12 |
| IV-07 | Manual payment recorded by platform admin | Invoice marked paid, billing_payment created with `manual` source |

---

### 13.15 Total test count estimate per phase

| Phase | Service / unit tests | Integration tests | Total |
|-------|--------------------|--------------------|-------|
| Phase 1A | ~50 | ~15 | ~65 |
| Phase 1B | ~45 | ~10 | ~55 |
| Phase 1C | ~30 | ~10 | ~40 |
| **Total Phase 1** | **~125** | **~35** | **~160** |

This is the regression baseline. Every Phase 2 module commit runs all 160 tests before merge.

---

## 14. Recommendation For Next Step

After review of this document:

1. Freeze Phase 1A schema — all tenant/branch/user/role tables
2. Freeze Phase 1B billing catalog — plan structure, module codes, capacity limit codes, state machine
3. Freeze feature code catalog and capacity tier definitions (these become seed data)
4. Decide payment gateway (Razorpay recommended, Stripe secondary)
5. Begin Phase 1A migration design and implementation
6. Plan Phase 1B schema alongside 1A so billing tables are ready when 1A stabilises

Only after Phase 1A and 1B are stable should Phase 1C (commerce) and module work begin.
