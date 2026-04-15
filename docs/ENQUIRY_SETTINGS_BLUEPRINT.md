# EDCRM SaaS Enquiry Settings Blueprint

## 1. Purpose

This document defines the first clean enquiry-settings contract for EDCRM SaaS.

It is intentionally narrow.

We are not importing every old JBKCRM setting.
We are only bringing the enquiry settings that are required to make enquiry visibility, duplicate control, assignment, and lifecycle behavior predictable.

This keeps the product:

- simpler for customers
- easier to test
- easier to explain
- safer for multi-tenant SaaS

---

## 2. First-Release Scope

Release 1 enquiry settings should cover only:

1. enquiry visibility
2. duplicate checking
3. assignment and transfer
4. expiry and closure

We should not add admissions, jobs, reports, or generic company-wide visibility settings into this screen.

---

## 3. Enquiry Settings Page

Path:

- `Settings > Enquiry Settings`

Sections:

1. Visibility
2. Duplicate Checking
3. Assignment
4. Expiry and Closure

Managed by:

- tenant owner
- tenant admin with settings privilege

Read-only for:

- branch managers
- normal staff

Platform admin:

- can override through platform policy workspace
- should not normally edit tenant values directly unless in support flow

---

## 4. Exact Fields

### 4.1 Visibility

#### `enquiry.visibility.mode`

Purpose:
- decides who can see enquiry records by default

Type:
- enum

Allowed values:

- `self`
- `assigned_branches`
- `company`

UI labels:

- `Only the enquiry owner`
- `People in assigned branches`
- `Everyone in this company`

Behavior:

- `self`
  Only the enquiry owner can view and work on the enquiry, unless a higher role has broader role-driven authority.
- `assigned_branches`
  Users assigned to the enquiry's branch and users whose branch assignments include that branch can view and work on the enquiry.
- `company`
  Any allowed company user can view and work on the enquiry, subject to role privileges.

Default:

- `assigned_branches`

#### `enquiry.visibility.allow_cross_branch_transfer`

Purpose:
- controls whether an enquiry can be moved from one branch to another

Type:
- bool

UI labels:

- `Allow moving enquiries across branches`

Allowed values:

- `0`
- `1`

Default:

- `1`

#### `enquiry.visibility.show_closed_to_all`

Purpose:
- controls whether closed enquiries follow the same normal visibility rule or remain restricted

Type:
- bool

UI label:

- `Keep closed enquiries visible based on normal access rules`

Default:

- `1`

#### `enquiry.visibility.show_expired_to_all`

Purpose:
- controls whether expired enquiries remain visible based on the normal rule

Type:
- bool

UI label:

- `Keep expired enquiries visible based on normal access rules`

Default:

- `1`

---

### 4.2 Duplicate Checking

#### `enquiry.duplicate.match_mode`

Purpose:
- defines how enquiry duplicates are detected

Type:
- enum

Allowed values:

- `email_and_mobile`
- `email_only`
- `mobile_only`
- `email_or_mobile`

UI labels:

- `Email and mobile both match`
- `Email only`
- `Mobile only`
- `Email or mobile`

Default:

- `email_or_mobile`

#### `enquiry.duplicate.scope`

Purpose:
- decides where duplicate checking is applied

Type:
- enum

Allowed values:

- `same_branch`
- `company`

UI labels:

- `Within the same branch`
- `Across the whole company`

Default:

- `company`

#### `enquiry.duplicate.action`

Purpose:
- decides what happens when a duplicate is found

Type:
- enum

Allowed values:

- `warn`
- `block`

UI labels:

- `Show warning and continue`
- `Stop creation`

Default:

- `warn`

---

### 4.3 Assignment

#### `enquiry.assignment.mode`

Purpose:
- defines how a new enquiry gets its first owner

Type:
- enum

Allowed values:

- `manual`
- `branch_round_robin`
- `branch_default_owner`

UI labels:

- `Assign manually`
- `Round robin inside branch`
- `Use branch default owner`

Default:

- `manual`

#### `enquiry.assignment.reassign_allowed`

Purpose:
- controls whether users with the right privilege can reassign enquiries later

Type:
- bool

UI label:

- `Allow reassignment after creation`

Default:

- `1`

---

### 4.4 Expiry and Closure

#### `enquiry.lifecycle.expiry_days`

Purpose:
- number of inactive days after which an enquiry becomes expired

Type:
- int

Default:

- `30`

#### `enquiry.lifecycle.auto_close_days`

Purpose:
- number of inactive days after which an enquiry is automatically closed

Type:
- int

Default:

- `60`

#### `enquiry.lifecycle.reopen_expired_allowed`

Purpose:
- whether expired enquiries can be reopened

Type:
- bool

Default:

- `1`

#### `enquiry.lifecycle.reopen_closed_allowed`

Purpose:
- whether closed enquiries can be reopened

Type:
- bool

Default:

- `0`

---

## 5. DB Keys

These should be stored using the existing settings catalog model.

### Tenant-level keys

- `enquiry.visibility.mode`
- `enquiry.visibility.allow_cross_branch_transfer`
- `enquiry.visibility.show_closed_to_all`
- `enquiry.visibility.show_expired_to_all`
- `enquiry.duplicate.match_mode`
- `enquiry.duplicate.scope`
- `enquiry.duplicate.action`
- `enquiry.assignment.mode`
- `enquiry.assignment.reassign_allowed`
- `enquiry.lifecycle.expiry_days`
- `enquiry.lifecycle.auto_close_days`
- `enquiry.lifecycle.reopen_expired_allowed`
- `enquiry.lifecycle.reopen_closed_allowed`

### Optional branch override keys for later

We should not implement these in the first enquiry settings release unless needed:

- `branch.enquiry.visibility.mode_override`
- `branch.enquiry.assignment.mode_override`
- `branch.enquiry.expiry_days_override`

For now:

- branch-level enquiry policy should stay out unless a real customer need forces it

---

## 6. `setting_definitions` Seed Proposal

Seed these records:

| key | scope | category | module_code | value_type | default |
|---|---|---|---|---|---|
| `enquiry.visibility.mode` | `tenant` | `enquiry_visibility` | `crm_core` | `enum` | `assigned_branches` |
| `enquiry.visibility.allow_cross_branch_transfer` | `tenant` | `enquiry_visibility` | `crm_core` | `bool` | `true` |
| `enquiry.visibility.show_closed_to_all` | `tenant` | `enquiry_visibility` | `crm_core` | `bool` | `true` |
| `enquiry.visibility.show_expired_to_all` | `tenant` | `enquiry_visibility` | `crm_core` | `bool` | `true` |
| `enquiry.duplicate.match_mode` | `tenant` | `enquiry_duplicate` | `crm_core` | `enum` | `email_or_mobile` |
| `enquiry.duplicate.scope` | `tenant` | `enquiry_duplicate` | `crm_core` | `enum` | `company` |
| `enquiry.duplicate.action` | `tenant` | `enquiry_duplicate` | `crm_core` | `enum` | `warn` |
| `enquiry.assignment.mode` | `tenant` | `enquiry_assignment` | `crm_core` | `enum` | `manual` |
| `enquiry.assignment.reassign_allowed` | `tenant` | `enquiry_assignment` | `crm_core` | `bool` | `true` |
| `enquiry.lifecycle.expiry_days` | `tenant` | `enquiry_lifecycle` | `crm_core` | `int` | `30` |
| `enquiry.lifecycle.auto_close_days` | `tenant` | `enquiry_lifecycle` | `crm_core` | `int` | `60` |
| `enquiry.lifecycle.reopen_expired_allowed` | `tenant` | `enquiry_lifecycle` | `crm_core` | `bool` | `true` |
| `enquiry.lifecycle.reopen_closed_allowed` | `tenant` | `enquiry_lifecycle` | `crm_core` | `bool` | `false` |

---

## 7. Who Can Edit What

### Platform Admin

Can:

- set platform lock or override values
- enforce company-wide support policy
- inspect effective settings during support

Should edit through:

- `Platform > Company Policy`

### Tenant Owner

Can:

- edit all unlocked enquiry settings

### Tenant Admin

Can:

- edit all unlocked enquiry settings if granted `settings.edit`

### Branch Manager

Cannot edit tenant enquiry policy in release 1.

Why:

- enquiry behavior must stay company-consistent first
- branch-specific exceptions can create confusion too early

### Staff

Cannot edit enquiry settings.

---

## 8. Resolution Rules

For release 1:

1. platform override
2. tenant value
3. setting default

Branch override is intentionally out of scope for now.

That keeps the behavior simple and easy to debug.

---

## 9. Runtime Rules

### Visibility rule

When a user opens enquiry lists or enquiry detail:

- first check role privilege
- then check tenant entitlement
- then apply `enquiry.visibility.mode`

Effective meaning:

- `self`
  user sees only enquiries where `owner_user_id = current_user_id`
- `assigned_branches`
  user sees enquiries where `branch_id` belongs to user's assigned branches
- `company`
  user sees all enquiries in same tenant

### Duplicate rule

When creating a new enquiry:

- read duplicate mode
- read duplicate scope
- search in same branch or whole tenant
- block or warn based on duplicate action

### Assignment rule

When a new enquiry is created:

- if `manual`, creator chooses owner
- if `branch_round_robin`, system assigns from branch pool
- if `branch_default_owner`, assign configured branch owner

### Lifecycle rule

Nightly or scheduled rule later:

- mark expired after `expiry_days`
- auto-close after `auto_close_days`

---

## 10. UI Contract

Screen title:

- `Enquiry Settings`

Section titles:

- `Who can see enquiries`
- `Duplicate checking`
- `How enquiries are assigned`
- `Expiry and closure`

Visibility dropdown options:

- `Only the enquiry owner`
- `People in assigned branches`
- `Everyone in this company`

Do not use:

- `public`
- `private`
- `custom`
- `project level`
- `company level visibility`

These are less clear for the EDCRM SaaS product.

---

## 11. What We Are Not Bringing Right Now

Do not bring these into enquiry settings release 1:

- generic project-level visibility
- admission visibility
- job visibility
- report visibility
- branch-level exception matrix
- too many duplicate sub-rules
- too many custom visibility combinations

Reason:

- they increase confusion faster than value
- they are harder to test
- they make support harder

---

## 12. Implementation Sequence

1. seed `setting_definitions` for enquiry keys
2. create enquiry settings screen
3. save tenant values through existing catalog-driven settings flow
4. update resolver tests for enquiry keys
5. consume enquiry visibility in enquiry list and detail
6. consume duplicate settings in enquiry create flow
7. consume assignment mode in enquiry create flow
8. consume expiry/closure later when lifecycle job is added

---

## 13. Definition of Done

This blueprint is complete when:

- enquiry settings screen exists
- tenant owner/admin can save enquiry settings
- platform policy can lock or override enquiry settings
- enquiry list obeys visibility mode
- duplicate logic obeys duplicate mode and scope
- assignment follows configured mode
- tests cover all three visibility modes
- tests cover duplicate blocking and warning behavior

---

## 14. Recommended First Build

Build only these first:

1. `enquiry.visibility.mode`
2. `enquiry.duplicate.match_mode`
3. `enquiry.duplicate.scope`
4. `enquiry.duplicate.action`
5. `enquiry.assignment.mode`

Then add:

6. transfer rule
7. expiry/closure rules

This keeps the first enquiry release stable.
