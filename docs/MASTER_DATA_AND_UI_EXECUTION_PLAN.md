# EDCRM SaaS: Master-Data and Tenant UI Execution Plan

## Purpose

We are entering the "best UX first" phase before enquiry and admissions modules.

Goal:

- Keep language simple for tenants (no internal terms)
- Make master-data and core admin screens mobile-friendly
- Reduce support load by making every action self-explanatory

This plan is ready to execute and verify on droplet.

---

## Current State

Tenant-facing admin screens already rewritten with friendlier labels:

- Branches (`app/Views/branches/*`)
- Users (`app/Views/users/*`)
- Roles (`app/Views/roles/*`)
- Settings (`app/Views/settings/index.php`)
- Master data (`app/Views/master_data/index.php`)
- Platform master data (`app/Views/platform/master_data/index.php`)

Still to normalize:

- Some wording is still mixed (tenant, subscription, branch technical labels remain)
- Some tables need mobile-first behavior checks
- Shared glossary is needed for new screens and report pages

---

## Language Standardization (Before -> After)

Use this mapping in all future copy updates and PR reviews.

| Before | After |
|---|---|
| Tenant | Company |
| Tenant owner | Company owner |
| Institute | Company |
| Slug | Company ID |
| Force reset | Set password now |
| Role-based permission | What each team member can do |
| Subscription | Plan |
| Trial mode | Intro trial |
| Module/feature | Team capability |
| Enquiry | New lead |
| Branch manager | Branch lead |
| Data scope | Team access |
| Platform lock | Admin lock |
| Hide value | Hide from this company |
| System values | Shared values |

Rules:

- Keep database names technical in code.
- UI labels must always use "After" wording.

---

## Master Data Contract (Tenant)

Route:

- `GET /settings/master-data?type=...` -> effective lists for current company

Screen structure:

- Header context (type name + short description)
- Shared values block with visibility status
- Company custom values block with active / inactive
- Add value form (only if allowed for that list)

Rules:

- Do not show technical identifiers (`code`, `scope`, `is_system`) to tenants.
- Tenants can:
  - Add company-specific values
  - Hide shared values from their company
  - Re-enable hidden shared values

Copy policy:

- Button labels should be business language, for example:
  - Add value
  - Turn on
  - Turn off
  - Hide from company

---

## Master Data Contract (Platform)

Route:

- `GET /platform/master-data`

Screen structure:

- Master data menu
- Selected type details
- Add shared value
- Shared values list
- Optional advanced type creation (collapsed section)

Rules:

- Shared catalog remains under platform control
- Tenant overrides are not platform-level items

---

## Plan Details and Reports Non-Technical Polish

Apply to pages that currently use technical status language:

- Plan summary
- Billing details
- Report access

Use:

- "Current plan" instead of "active subscription"
- "Team size" instead of "active user cap"
- "People allowed" instead of "license count"
- "Add-on services" instead of "feature toggle"

Report screens should always show:

- What is available
- What is locked
- Where to unlock it

---

## Responsive UX Rules

1. Primary action is always visible and reachable.
2. Tables switch to stacked/cards on mobile (< 768px).
3. Keep 6-8 form fields per visual block.
4. Limit actions per row to 3.
5. Empty states must include next action text.

---

## Execution Steps (This Week)

### Step 1 - Screen text standardization

- Apply the glossary to:
  - Users
  - Roles
  - Branches
  - Tenant settings
  - Tenant master data
  - Platform master data
  - Plan and billing pages
- Keep backend identifiers unchanged.

### Step 2 - Mobile behavior verification

- Validate following routes on 390px, 768px widths:
  - `/users`
  - `/users/create`
  - `/roles`
  - `/branches`
  - `/settings/master-data`
  - `/platform/master-data`

Acceptance:

- no horizontal scroll
- action buttons remain visible
- labels readable without zooming

### Step 3 - Copy harmonization PR

- One PR containing only UI copy and small spacing updates.
- No logic changes in this pass.

### Step 4 - End-to-end smoke coverage

- Add Playwright smoke tests for:
  - platform admin login
  - tenant login
  - create branch
  - create team member
  - role edit
  - master-data add/hide

---

## Definition of Done

- Single shared vocabulary used in all tenant-facing screens.
- Master-data actions are readable for non-technical customers.
- Mobile screen works on 390px width.
- CI passes unit/feature/session/database tests.
- Playwright smoke suite added for the core screens above.

---

## Status Board

- Users: done
- Roles: done
- Branches: done
- Tenant settings: done
- Tenant master data: done
- Platform master data: done
- Plan and subscription screens: done (wording review pending)
- Report screens: pending
- Playwright smoke tests: pending
