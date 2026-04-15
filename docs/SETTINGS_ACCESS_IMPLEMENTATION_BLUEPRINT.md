# EDCRM SaaS Settings and Access Implementation Blueprint

## 1. Purpose

This blueprint defines the implementation contract for:

- platform tenant policy overrides
- tenant settings
- branch settings
- settings resolution
- user hierarchy and access scopes
- impersonation groundwork

This is the build reference before deep Enquiry implementation starts.

---

## 2. Page Structure

### 2.1 Platform Tenant Policy
Managed by: platform admin only

Tabs:

1. Subscription
- plan
- billing status
- trial start
- trial end
- grace period days
- restriction mode in grace
- suspension mode
- next renewal date
- auto-renew

2. Modules
- crm_core
- enquiries
- admissions
- fees
- service
- placement
- reports
- whatsapp
- audit

3. Limits
- max users
- max branches
- max active enquiries
- max monthly emails
- max monthly WhatsApp messages
- max exports

4. Policy Locks
- lock timezone
- lock currency
- lock branding
- lock SMTP
- lock WhatsApp
- lock enquiry policy
- lock duplicate policy
- lock branch overrides
- lock impersonation

5. Support Access
- allow platform impersonation
- require reason
- notify tenant owner
- session timeout
- support can impersonate owner
- support can impersonate branch users only

### 2.2 Tenant Settings
Managed by: tenant owner / tenant admin, subject to platform locks

Tabs:

1. Profile
- institute display name
- legal name
- tenant code / slug
- primary contact name
- primary email
- primary phone
- website
- address
- support contact

2. Branding
- logo
- primary color
- secondary color
- login header text
- footer text
- support email shown in app

3. Regional
- timezone
- currency
- locale
- country
- fiscal year start month
- week start day
- date format
- time format

4. Visibility
- branch visibility mode
- enquiry visibility mode
- report visibility mode
- cross-branch visibility mode
- expired enquiry visibility mode
- closed enquiry visibility mode

5. Security
- password minimum length
- password complexity
- password history count
- force reset on first login
- password expiry days
- session timeout minutes
- failed login limit
- account lock duration
- allow tenant impersonation
- require impersonation reason

6. Communication
- SMTP host
- SMTP port
- SMTP username
- SMTP password
- SMTP encryption
- from name
- from email
- reply-to email
- WhatsApp provider
- WhatsApp sender id
- WhatsApp API token

7. Operations
- default branch
- default enquiry owner mode
- branch override policy
- branch transfer allowed
- default dashboard scope

8. Enquiry Policy
- enquiry expiry days
- auto-close inactive enquiry days
- expired enquiry status
- closed enquiry status
- reopen expired allowed
- reopen closed allowed
- excluded sources from expiry
- duplicate scope
- duplicate action
- assignment mode
- fallback assignee
- round robin users

### 2.3 Branch Settings
Managed by: tenant owner / tenant admin / branch manager if allowed

Tabs:

1. Branch Profile
- branch name
- branch code
- branch email
- branch phone
- address
- branch manager
- status
- opening date

2. Regional Override
- inherit tenant regional settings
- timezone override
- currency override
- locale override
- working days
- working hours

3. Operations
- default branch owner
- default queue
- assignment mode
- round robin users
- transfer permission

4. Visibility
- can see other branches
- can view tenant-wide reports
- can reopen expired enquiries
- can transfer enquiries out

5. Enquiry Override
- inherit tenant enquiry settings
- expiry days override
- auto-close days override
- duplicate scope override
- duplicate action override
- allowed sources
- excluded sources
- expired enquiry review user

Communication stays tenant-level in the first cut.

---

## 3. Editability Matrix

### 3.1 Platform Admin
Can edit:

- all platform tenant policy values
- all module entitlements
- all limits
- all policy locks
- all support access controls
- tenant settings for support purposes
- branch settings for support purposes

### 3.2 Tenant Owner
Can edit:

- all unlocked tenant settings
- all branch settings inside tenant
- impersonation inside tenant if enabled

### 3.3 Tenant Admin
Can edit:

- tenant settings allowed by privilege
- branch settings allowed by privilege
- no billing or locked policy fields unless explicitly granted

### 3.4 Branch Manager
Can edit:

- own branch settings only
- branch operational overrides if tenant/platform allows

### 3.5 Staff
Can edit:

- own profile/preferences only

### 3.6 Lock Modes

All settings support these lock modes:

- editable
- tenant_locked
- branch_locked
- platform_enforced

Runtime meaning:

- editable: normal inheritance applies
- tenant_locked: tenant cannot edit, branch also cannot override
- branch_locked: tenant can edit, branch cannot override
- platform_enforced: platform value wins

---

## 4. Database Blueprint

### 4.1 setting_definitions

Master catalog of supported settings.

Fields:

- id
- key
- label
- description
- scope
- category
- module_code
- value_type
- allowed_options_json
- default_value_json
- is_sensitive
- is_active
- sort_order
- created_at
- updated_at

### 4.2 tenant_setting_values

Tenant-specific values.

Fields:

- id
- tenant_id
- key
- value
- value_type
- created_at
- updated_at

### 4.3 branch_setting_values

Branch-specific override values.

Fields:

- id
- tenant_id
- branch_id
- key
- value
- value_type
- created_by
- updated_by
- created_at
- updated_at

### 4.4 tenant_policy_overrides

Platform-controlled override and lock records.

Fields:

- id
- tenant_id
- key
- override_value
- value_type
- lock_mode
- notes
- created_by
- updated_by
- created_at
- updated_at

### 4.5 impersonation_sessions

Audit trail for support and delegated login-as flows.

Fields:

- id
- tenant_id
- actor_user_id
- target_user_id
- reason
- started_at
- ended_at
- actor_ip
- actor_user_agent
- created_at
- updated_at

### 4.6 user access model

Keep on users:

- allow_impersonation

Keep org chart in user_hierarchy:

- user_id
- manager_user_id
- acting_manager_user_id

Drive visibility from role-level `access_behavior`:

- `hierarchy` -> self plus downline users
- `branch` -> assigned branch coverage
- `tenant` -> full tenant visibility

Normal user administration should not expose free-form scope pickers. The selected role determines behavior, branch assignments define operational coverage, and `user_hierarchy` defines downline visibility for hierarchy-driven roles.

---

## 5. Settings Resolution Contract

Service: `SettingsResolverService`

Resolution order:

1. platform override
2. branch override
3. tenant value
4. setting default

The resolver is the only runtime source of truth. Controllers and modules should not hardcode settings precedence.

---

## 6. User Hierarchy and Access Model

Hierarchy and visibility stay separate.

### 6.1 Reporting hierarchy
Answers: who reports to whom?

Storage:
- `user_hierarchy.manager_user_id`
- `user_hierarchy.acting_manager_user_id`

### 6.2 Runtime access scope
Answers: whose records can this user see and manage?

EDCRM now keeps normal create/edit flows lighter:

- roles define privileges
- branch assignment defines branch coverage
- reporting relationships define hierarchy coverage
- tenant owner and platform roles retain broader visibility by role code and policy

That means normal forms no longer ask users to manually choose low-level `data_scope`, `manage_scope`, or `hierarchy_mode` values. Those internal concepts are resolved by services from the user's assigned branches, reporting manager chain, and privileged role type.

Examples:

- counsellor: own records, manager assigned in `user_hierarchy`
- team lead: own records plus downline
- operations user: assigned branch coverage
- branch manager: assigned branch coverage with elevated management privileges
- tenant owner: tenant-wide visibility

---

## 7. Impersonation Contract

This is login-as behavior with audit, not silent auto login.

### Platform support impersonation
- platform user can impersonate tenant user if policy allows
- reason required if configured
- tenant owner notification optional
- session timeout enforced
- banner shown during impersonation
- exit back to original account

### Tenant impersonation
- tenant owner/admin can impersonate users in same tenant if allowed
- cannot impersonate platform users
- cannot impersonate higher authority user

### Branch impersonation
- branch manager can impersonate branch staff if allowed
- cannot impersonate tenant owner/admin

---

## 8. Initial Setting Keys to Seed

Seed now:

- tenant.profile.display_name
- tenant.profile.legal_name
- tenant.profile.support_email
- tenant.regional.timezone
- tenant.regional.currency
- tenant.regional.locale
- tenant.regional.week_start_day
- tenant.visibility.branch_mode
- tenant.visibility.enquiry_mode
- tenant.visibility.expired_enquiry_mode
- tenant.security.password_min_length
- tenant.security.password_history_count
- tenant.security.session_timeout_minutes
- tenant.security.allow_impersonation
- tenant.security.require_impersonation_reason
- enquiry.policy.expiry_days
- enquiry.policy.auto_close_inactive_days
- enquiry.policy.duplicate_scope
- enquiry.policy.duplicate_action
- enquiry.policy.assignment_mode
- enquiry.policy.exclude_sources_from_expiry
- branch.regional.inherit_tenant_defaults
- branch.operations.assignment_mode
- branch.enquiry.expiry_days_override
- branch.enquiry.duplicate_scope_override
- platform.support.allow_impersonation
- platform.support.notify_tenant_owner_on_impersonation
- platform.support.impersonation_session_timeout_minutes

---

## 9. Implementation Sequence

### Wave 1 - foundation
- add settings definition/value/override tables
- add settings resolver service
- add user access-scope columns
- add impersonation session audit table

### Wave 2 - settings management
- tenant settings tabs and save endpoints
- branch settings tabs and save endpoints
- platform tenant policy UI

### Wave 3 - access and hierarchy
- user form: reporting head, data scope, manage scope
- delegation validation for scopes
- manager-aware user lists

### Wave 4 - consumption
- enquiry module reads effective settings
- duplicate rules and expiry rules use resolver
- impersonation flow uses policy settings

---

## 10. Definition of Done Before Enquiry

This foundation is ready when:

- setting catalog exists
- tenant and branch overrides persist cleanly
- platform locks work
- effective settings resolve correctly
- user reporting head can be stored
- user data/manage scope can be stored
- impersonation auditing exists
- Enquiry can read expiry, duplicate, assignment, and visibility policy from resolver instead of hardcoding

---

## 11. Current Delivery Status

Last updated: 2026-04-14

Delivered:

- settings schema foundation
- `SettingsResolverService`
- Tenant Settings v2 screen
- Branch Settings module
- Platform Tenant Policy workspace
- user reporting head and scope fields
- scope enforcement on user create/edit and target-user management
- impersonation service, controller flow, audit persistence, and shell banner
- PHPUnit coverage for:
  - protected routes
  - auth database flows
  - user access scope rules
  - impersonation service rules
  - impersonation/branch-policy route protection

Next hardening before Enquiry:

- add deeper billing override tests
- add settings resolver tests for lock precedence
- add branch/tenant policy save feature tests
- add impersonation notification behavior if/when implemented
