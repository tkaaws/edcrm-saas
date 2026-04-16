# EDCRM Enquiry Module Implementation Blueprint

## Purpose

This document converts the enquiry-module product plan into a developer-ready implementation blueprint for EDCRM SaaS.

It is intentionally not a direct copy of JBKCRM. It keeps the useful operating model from JBKCRM:

- queue-based enquiry handling
- follow-up driven work management
- reassignment tracking

and removes the legacy clutter:

- too many tabs
- mixed status meanings
- assignment audit hidden in remarks
- hierarchy behavior spread across ad hoc queries

This blueprint is the build contract for EDCRM Phase 1A and Phase 1B enquiry delivery.

## Design Principles

1. Keep enquiry lifecycle status small.
2. Treat most tabs as derived work queues, not stored statuses.
3. Separate enquiry status, follow-up outcome, and assignment history.
4. Apply visibility through role, branch, hierarchy, and settings instead of duplicate tabs like `All` and `Mine`.
5. Hide internal platform concepts from company users.
6. Keep the first release operationally strong and structurally clean.

## Scope Split

### Phase 1A

- enquiry create/edit/view
- enquiry list workspace
- core queue tabs
- follow-up timeline
- close/reopen
- convert to admission
- assignment history capture
- visibility and hierarchy enforcement

### Phase 1B

- manual reassignment
- bulk assignment
- reassignment tracker
- duplicate detection and duplicate queue
- manager-oriented audit views
- saved filters or common queue filters if needed

## Enquiry Navigation Model

V1 navigation should not overload one page with every operational queue.

### Enquiry menu structure

Under the main `Enquiry` menu, create these submenu items:

1. `Enquiries`
2. `Expired Enquiries`
3. `Closed Enquiries`
4. `Bulk Assign`

### Related setup menu

Add a separate CRUD screen for `Colleges`.

This should behave like other company-managed master modules such as roles or branches:

- list colleges
- add college
- edit college
- disable/delete college as per business rule

Recommended placement:

- company-facing menu item: `Colleges`
- not hidden inside technical master-data internals

### College availability rule

For Phase 1, `Colleges` is standard for all companies.

That means:

- no company-level dependency logic is needed
- no feature flag is needed for college capture in enquiry
- college dropdown is always shown in enquiry create/edit
- if college master is empty for a company, seed a default placeholder record

Recommended default seed:

- `Test College`

### Enquiries workspace tabs

Inside the main `Enquiries` workspace, keep only the active working queues:

1. `Enquiries`
2. `Today`
3. `Missed`
4. `Fresh`

These are intentionally excluded from V1 tabs:

- `Mine`
- `Branch Level`
- `Company Level`
- `Positive Followup`
- `Negative Followup`
- `Closed by Others`
- `Follow-up Added by Others`
- `Assigned to Others`
- `Duplicates`

These are intentionally separated as submenu screens instead of tabs:

- `Expired Enquiries`
- `Closed Enquiries`
- `Bulk Assign`

Those behaviors will be covered by:

- hierarchy visibility
- role-based filters
- owner filters
- activity/history screens

## Lifecycle Model

### Enquiry lifecycle status

Store only the true lifecycle state on the enquiry record:

- `new`
- `active`
- `closed`
- `admitted`

### Derived queue state

Do not store these as primary lifecycle values:

- `fresh`
- `due_today`
- `missed`
- `expired`

These are queue results produced from timestamps and follow-up history.

### Important expiry rule

`expired` must never be stored as the main enquiry status.

It must always be computed from:

- current lifecycle status = `new` or `active`
- last active manual follow-up timestamp
- enquiry expiry settings

Reason:

- expiry changes with time
- a stored expiry status becomes stale
- one new manual follow-up can immediately make the enquiry non-expired again

## Follow-up Model

Follow-up outcome must be independent from enquiry lifecycle.

### Initial follow-up outcome master

1. `Interested`
2. `Not Interested`
3. `Not Reachable`
4. `Busy / Call Later`
5. `Visit Expected`
6. `Visit Done`
7. `After Demo`

### Optional later additions

- `Hot`
- `Cold`
- `Lost to Competitor`

The first release should stay narrow so reporting remains usable.

## Queue Definitions

### `Enquiries`

Visible enquiries where:

- lifecycle status in `new`, `active`
- user has access by company/branch/hierarchy settings

### `Today`

Visible enquiries where:

- lifecycle status in `new`, `active`
- `next_followup_at` falls on current local date

### `Missed`

Visible enquiries where:

- lifecycle status in `new`, `active`
- `next_followup_at` is before current local date

### `Fresh`

Visible enquiries where:

- lifecycle status in `new`, `active`
- no manual follow-up exists yet

### `Expired`

Visible enquiries where:

- lifecycle status in `new`, `active`
- expiry policy is crossed from the last active manual follow-up date
- no valid recent manual follow-up keeps the enquiry active

### `Closed`

Visible enquiries where:

- lifecycle status = `closed`

## Settings Dependencies

This module depends on enquiry settings already planned in EDCRM.

### Phase 1A settings consumed

- `enquiry.visibility.mode`
  - `self`
  - `assigned_branches`
  - `company`
- `enquiry.expiry.days`
- `enquiry.auto_close.days` (optional for later enforcement)

### Not needed in initial implementation

- duplicate queue
- round robin routing
- source-specific auto assignment
- lead scoring
- advanced SLA workflows

## Role and Visibility Behavior

There should be no dedicated `Mine` tab.

### Visibility comes from

- authenticated company
- branch access
- hierarchy mapping
- role privileges
- enquiry visibility setting

### Expected behavior by role

#### Counsellor

- sees only enquiries allowed by current visibility mode
- typically own enquiries in `self`
- can work follow-ups and update owned enquiries

#### Branch Manager

- sees branch-level team enquiries when policy allows
- can reassign inside allowed branch scope
- can monitor due, missed, expired queues for the branch

#### Company Admin / Owner

- sees company-wide visible enquiries when policy allows
- can manage reassignment and configuration

## Privilege Model

Yes, this module should introduce dedicated enquiry privileges, and company admins should be able to assign them to their employees through tenant role management.

### Important masking rule

Add one explicit privilege for mobile visibility:

- `enquiry.view_mobile_number`

Behavior:

- if user has this privilege, the real mobile number is visible
- if user does not have this privilege, the number is masked everywhere in enquiry lists, enquiry detail, follow-up screens, bulk assignment results, exports, and search results
- if masking is enabled for the user, no unmasked enquiry mobile number should be exposed anywhere in UI

Recommended masked format:

- `98xxxxxx21`

The same rule should later be mirrored for WhatsApp or secondary numbers if those are shown in the module.

### Phase 1A enquiry privileges

- `enquiry.menu_access`
- `enquiry.list`
- `enquiry.view`
- `enquiry.create`
- `enquiry.edit`
- `enquiry.add_followup`
- `enquiry.close`
- `enquiry.reopen`
- `enquiry.convert_to_admission`
- `enquiry.view_mobile_number`
- `enquiry.view_created_by`
- `enquiry.view_modified_by`
- `enquiry.view_created_on`
- `enquiry.view_modified_on`

### Phase 1B enquiry privileges

- `enquiry.reassign_in_edit`
- `enquiry.expired_assign`
- `enquiry.closed_assign`
- `enquiry.bulk_assign`
- `enquiry.assignment_history_view`
- `enquiry.activity_view`
- `enquiry.duplicate_queue_view`
- `enquiry.duplicate_resolve`

### Role assignment rule

- system defines the available privilege catalog
- company admin configures tenant roles using that catalog
- tenant employees inherit capabilities only through their assigned role

### Privilege notes by feature

#### Active / New enquiries

- list access needs `enquiry.list`
- detail access needs `enquiry.view`
- edit access needs `enquiry.edit`
- reassignment field inside edit needs `enquiry.reassign_in_edit`

#### Expired enquiries

- submenu visibility needs `enquiry.list`
- quick assign action needs `enquiry.expired_assign`

#### Closed enquiries

- submenu visibility needs `enquiry.list`
- `Assign Closed Enquiry` needs `enquiry.closed_assign`
- close/reopen needs `enquiry.close` and `enquiry.reopen`

#### Bulk Assign

- menu access and execution both require `enquiry.bulk_assign`

#### Audit columns

- showing created/modified user/date columns can be kept always visible if business wants them globally
- if we want stricter control, keep them behind:
  - `enquiry.view_created_by`
  - `enquiry.view_modified_by`
  - `enquiry.view_created_on`
  - `enquiry.view_modified_on`

## Data Model

## 1. `enquiries`

Primary enquiry record.

Suggested columns:

- `id`
- `tenant_id`
- `branch_id`
- `owner_user_id`
- `created_by`
- `updated_by`
- `first_name`
- `last_name`
- `full_name`
- `email`
- `mobile`
- `whatsapp_number`
- `source_id`
- `college_id`
- `qualification_id`
- `course_id` or `primary_course_id`
- `city`
- `notes`
- `lifecycle_status`
- `last_followup_at`
- `next_followup_at`
- `closed_at`
- `closed_by`
- `created_at`
- `updated_at`

Indexes:

- `(tenant_id, lifecycle_status)`
- `(tenant_id, branch_id, owner_user_id)`
- `(tenant_id, next_followup_at)`
- `(tenant_id, mobile)`
- `(tenant_id, email)`
- `(tenant_id, college_id)`

## 2. `enquiry_followups`

Stores every follow-up action.

Suggested columns:

- `id`
- `tenant_id`
- `enquiry_id`
- `branch_id`
- `owner_user_id`
- `communication_type_id`
- `followup_outcome_id`
- `remarks`
- `next_followup_at`
- `is_system_generated`
- `created_by`
- `created_at`

Indexes:

- `(tenant_id, enquiry_id, created_at desc)`
- `(tenant_id, created_by, created_at desc)`
- `(tenant_id, next_followup_at)`

## 3. `enquiry_assignment_history`

Source of truth for reassignment.

Suggested columns:

- `id`
- `tenant_id`
- `enquiry_id`
- `from_branch_id`
- `to_branch_id`
- `from_user_id`
- `to_user_id`
- `assigned_by`
- `assignment_type`
- `reason`
- `bulk_batch_id`
- `created_at`

Assignment types:

- `manual`
- `bulk_manual`
- `system`

Indexes:

- `(tenant_id, enquiry_id, created_at desc)`
- `(tenant_id, to_user_id, created_at desc)`
- `(tenant_id, bulk_batch_id)`

## 4. `colleges`

Company-managed college master.

Suggested columns:

- `id`
- `tenant_id`
- `name`
- `state_id`
- `city_id`
- `status`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Business rules:

- college name is required
- state is required
- city is required
- city should belong to selected state
- duplicate college names should be prevented within the same tenant where possible

Indexes:

- `(tenant_id, name)`
- `(tenant_id, state_id, city_id)`

## 5. `followup_outcomes`

Tenant-effective master of follow-up outcomes.

Suggested columns:

- `id`
- `tenant_id` nullable for system defaults
- `name`
- `status`
- `sort_order`
- `is_system`
- `created_at`
- `updated_at`

## 6. `communication_types`

Tenant-effective master for follow-up communication mode.

Suggested columns:

- `id`
- `tenant_id` nullable for system defaults
- `name`
- `status`
- `sort_order`
- `is_system`

## 7. `enquiry_status_logs`

Optional but recommended audit table for lifecycle changes.

Suggested columns:

- `id`
- `tenant_id`
- `enquiry_id`
- `from_status`
- `to_status`
- `changed_by`
- `reason`
- `created_at`

## Bulk Assignment Blueprint

Bulk assignment must be a workflow, not only a batch update.

### Supported Phase 1B actions

- assign selected enquiries to another user
- move selected enquiries to another branch
- move and assign in one action

### Business action rules

#### Active / New enquiries

- no quick assign action from list
- reassignment is done only inside `Edit Enquiry`
- reassignment field is shown only to users with reassignment privilege

#### Expired enquiries

- direct `Assign` action is allowed

#### Closed enquiries

- direct `Assign Closed Enquiry` action is allowed after search/view access

#### Admitted enquiries

- no enquiry reassignment
- admission or student workflow takes over from there

### Request payload shape

- `selected_enquiry_ids[]`
- `target_branch_id`
- `target_owner_user_id`
- `reason`

### Validation rules

1. user must have bulk assignment privilege
2. user must have visibility over all selected enquiries
3. target owner must belong to target branch or allowed branch scope
4. admitted/locked enquiries should be rejected if business rules block changes

### Write sequence

1. fetch selected enquiries
2. validate access and current state
3. update `enquiries.branch_id`
4. update `enquiries.owner_user_id`
5. update `enquiries.updated_by`
6. insert one `enquiry_assignment_history` row per enquiry
7. optionally insert one system follow-up note per enquiry
8. return success/failure counts

### Important rule

Assignment history table is the real audit trail.
System follow-up note is only a human-readable event.

## UI Blueprint

## 1. Enquiry Workspace Screen

### Header

- title: `Enquiries`
- queue tabs
- primary action: `Add enquiry`
- secondary actions:
  - filters
  - export later
  - bulk actions only when rows selected

### Filters

- owner
- branch
- source
- college
- qualification
- course
- date range
- due today only

### Mobile-first enquiry capture

Sales users will often add enquiries from mobile, so the create flow on phone must stay intentionally limited.

#### Mobile enquiry create fields

- Mobile
- Student name
- Source
- Course
- College
- Optional remarks

These should be enough for fast capture.

#### Not required in first mobile step

- long qualification details
- email
- multiple academic fields
- too many address fields
- branch reassignment controls

Recommended approach:

- Step 1 on mobile: quick capture
- Step 2 later on detail/edit: enrich remaining information

### College field behavior in enquiry form

- `College` must be a searchable dropdown
- dropdown source must come from company college master
- user should not type free-text college name into enquiry directly in V1
- if college list is empty for a company, system should ensure a default placeholder such as `Test College` exists

### Standard audit columns in every enquiry list

Every enquiry list or result grid should also include:

- Created on
- Modified on
- Created by
- Modified by

### Contextual columns by tab

#### `Enquiries`

- Name
- Mobile
- Source
- Course
- Branch
- Assigned to
- Queue status
- Created on
- Modified on
- Created by
- Modified by
- Actions

#### `Today`

- Name
- Mobile
- Source
- Course
- Assigned to
- Follow-up due time
- Created on
- Modified on
- Created by
- Modified by
- Actions

#### `Missed`

- Name
- Mobile
- Source
- Course
- Assigned to
- Due date
- Overdue by
- Created on
- Modified on
- Created by
- Modified by
- Actions

#### `Fresh`

- Name
- Mobile
- Source
- Course
- Branch
- Assigned to
- Created on
- Modified on
- Created by
- Modified by
- Actions

#### `Expired Enquiries`

- Name
- Mobile
- Source
- Course
- Branch
- Assigned to
- Last follow-up
- Expired on
- Created on
- Modified on
- Created by
- Modified by
- Actions

#### `Closed Enquiries`

- Name
- Mobile
- Source
- Course
- Branch
- Assigned to
- Closed by
- Closed on
- Created on
- Modified on
- Created by
- Modified by
- Actions

### Row actions

- view
- add follow-up
- close
- convert

Action visibility rules:

- `Assign` is not shown as a quick action for active/new enquiries
- active/new reassignment is available inside edit only
- `Assign` is shown for expired enquiries
- `Assign Closed Enquiry` is shown for closed enquiries
- no assign action for admitted enquiries

## 2. Enquiry Detail Screen

Tabs:

- `Overview`
- `Follow-ups`
- `Assignment History`
- `Activity`
- `Admission`

### Overview

- lead identity
- source
- course/qualification
- branch
- assigned to
- lifecycle status
- created on
- last follow-up
- next follow-up

### Follow-ups

- reverse timeline
- add follow-up form
- next follow-up scheduler

### Assignment History

- old owner
- new owner
- old branch
- new branch
- changed by
- reason
- timestamp

### Activity

- status changes
- conversion events

### Primary detail-page actions

- `Edit Enquiry`
- `Close Enquiry`
- `Add Follow-up`
- `Convert to Admission`

### Close Enquiry rule

Closing an enquiry must always ask for:

- close reason
- optional remarks

Suggested close reason list:

- Not Interested
- No Response
- Budget Issue
- Joined Elsewhere
- Invalid Enquiry
- Other

## 3. Bulk Assignment Screen / Drawer

Recommended as selection-driven action from main workspace.

Bulk Assign should also have its own submenu screen under `Enquiry`.

Sections:

- full filter area
- result table
- selected count
- target branch
- target owner
- assign reason
- confirm action

### Bulk Assign filters

Bulk Assign must support rich filtering before selection.

Recommended filters:

- branch
- source
- course
- qualification
- owner / assigned to
- created by
- lifecycle status
- created date range
- modified date range
- follow-up due date range
- fresh only
- missed only
- expired only
- search by name/mobile/email

### Bulk Assign result columns

- Name
- Mobile
- Source
- Course
- Branch
- Assigned to
- Queue status
- Created on
- Modified on
- Created by
- Modified by

### Bulk Assign assignment area

At the bottom of the Bulk Assign screen:

- allowed branches dropdown
- employee dropdown filtered by selected allowed branch
- assign reason

When bulk assignment succeeds, these fields change on the enquiry:

- `branch_id`
- `owner_user_id`
- `assigned_on`
- `updated_by`
- `updated_at`

And these audit values must remain visible in the result grid:

- Created on
- Modified on
- Created by
- Modified by

## API / Controller Blueprint

Suggested controller structure:

- `Colleges`
  - `index`
  - `list`
  - `create`
  - `store`
  - `edit`
  - `update`
  - `delete` or `status`
- `Enquiries`
  - `index`
  - `list`
  - `create`
  - `store`
  - `show`
  - `edit`
  - `update`
  - `close`
  - `reopen`
  - `convert`
- `EnquiryFollowups`
  - `store`
  - `listByEnquiry`
- `EnquiryAssignments`
  - `assign`
  - `bulkAssign`
  - `history`

## Service Blueprint

Recommended services:

- `CollegeService`
- `EnquiryVisibilityService`
- `EnquiryQueueService`
- `EnquiryAssignmentService`
- `EnquiryFollowupService`
- `EnquiryConversionService`

Responsibilities:

### `CollegeService`

- validate state and city relationship
- provide searchable college dropdown data
- enforce company-level uniqueness rules
- ensure default placeholder college exists for company bootstrap if needed

### `EnquiryVisibilityService`

- build tenant/branch/hierarchy scoped query conditions

### `EnquiryQueueService`

- apply queue rules for:
  - today
  - missed
  - fresh
  - expired
  - closed

### `EnquiryAssignmentService`

- single assign
- bulk assign
- assignment history write

### `EnquiryFollowupService`

- add follow-up
- maintain `last_followup_at`
- maintain `next_followup_at`

### `EnquiryConversionService`

- convert enquiry to admission
- change lifecycle status to `admitted`

## Recommended Migration Order

### Migration 1

Create:

- `enquiries`
- `colleges`
- `followup_outcomes`
- `communication_types`
- `enquiry_followups`
- `enquiry_assignment_history`
- `enquiry_status_logs`

### Migration 2

Add indexes and foreign keys.

### Seeder 1

Seed:

- base state/city data if not already available in system masters
- default follow-up outcomes
- default communication types
- default placeholder college such as `Test College` for company bootstrap

### Seeder 2

Seed demo enquiry data only if needed for development.

## Build Order

### Phase 1A build order

1. schema migrations
2. college CRUD schema and searchable lookup
3. master data seeders for outcomes and communication types
4. enquiry settings wiring
5. `CollegeService`
6. `EnquiryVisibilityService`
7. `EnquiryQueueService`
8. enquiry list workspace
9. enquiry create/edit/view
10. mobile quick enquiry capture
11. follow-up timeline and add-follow-up
12. close/reopen with close reason
13. convert to admission
14. assignment history logging on owner change

### Phase 1B build order

1. single reassignment UI
2. bulk assignment workflow
3. reassignment tracker queue
4. duplicate detection and duplicate queue
5. manager audit screens

## Reporting Notes

Do not derive reporting only from current enquiry row.

Use:

- enquiry current state
- follow-up events
- assignment history
- status logs

This avoids the JBKCRM problem where operational meaning is hidden inside remarks.

## Explicit Decisions

We are explicitly choosing:

- no `All` vs `Mine` tab duplication
- no branch/company duplicate tabs
- no storing `Expired` as a main status
- no storing expiry state in DB as a changing lifecycle value
- no assignment audit hidden only in follow-up text
- no overly large follow-up status master in V1
- no legacy college-drive-specific enquiry logic in core enquiry
- no duplicates tab in Phase 1A
- no free-text college entry inside enquiry V1
- no college dependency logic in Phase 1

## Open Items for Later, Not Blocking Phase 1

- automatic routing rules
- round robin assignment
- duplicate detection and merge handling
- source-wise assignment rules
- SLA breach escalations
- counsellor productivity dashboards
- queue saved views

## Implementation Summary

EDCRM enquiry module V1 should be:

- queue-based
- follow-up driven
- hierarchy-aware
- assignment-audited
- mobile-capture friendly
- supported by proper college master data
- structurally simple

That gives us a module that feels operationally strong like JBKCRM, but much cleaner to maintain and scale.
