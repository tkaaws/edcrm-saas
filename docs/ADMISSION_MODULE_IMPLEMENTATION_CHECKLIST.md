# EDCRM Admission Module Implementation Checklist

## Purpose

This document converts the admissions product plan into a build-ready implementation checklist for EDCRM SaaS.

It takes the useful operating spine from JBKCRM:

- enquiry to admission conversion
- fee structure selection
- first payment capture
- installment generation
- batch assignment
- payment recovery follow-up

and removes the legacy clutter:

- too many mixed actions on one screen
- overloaded statuses
- mutable finance summary logic without a clean ledger model
- too many tabs and filters visible at once

This is the execution contract for EDCRM admission delivery.

## JBKCRM Reference Surface

Primary references reviewed:

- [Admissions.php](E:/xampp/htdocs/jbkcrm/app/Controllers/Admin/Admissions.php)
- [admissions.php](E:/xampp/htdocs/jbkcrm/app/Views/admin/admissions.php)
- [admissions-modal.php](E:/xampp/htdocs/jbkcrm/app/Views/admin/admissions-modal.php)
- [Payments.php](E:/xampp/htdocs/jbkcrm/app/Controllers/Admin/Payments.php)
- [Installments.php](E:/xampp/htdocs/jbkcrm/app/Controllers/Admin/Installments.php)
- [Batches.php](E:/xampp/htdocs/jbkcrm/app/Controllers/Admin/Batches.php)
- [Changebatch.php](E:/xampp/htdocs/jbkcrm/app/Controllers/Admin/Changebatch.php)
- [Bulkassignadmissions.php](E:/xampp/htdocs/jbkcrm/app/Controllers/Admin/Bulkassignadmissions.php)
- [AdmissionsModel.php](E:/xampp/htdocs/jbkcrm/app/Models/AdmissionsModel.php)
- [StudentFeesModel.php](E:/xampp/htdocs/jbkcrm/app/Models/StudentFeesModel.php)
- [StudentInstallmentModel.php](E:/xampp/htdocs/jbkcrm/app/Models/StudentInstallmentModel.php)
- [StudentPaymentModel.php](E:/xampp/htdocs/jbkcrm/app/Models/StudentPaymentModel.php)
- [StudentBatchModel.php](E:/xampp/htdocs/jbkcrm/app/Models/StudentBatchModel.php)
- [FeeStructureModel.php](E:/xampp/htdocs/jbkcrm/app/Models/FeeStructureModel.php)
- [FeeDetailsModel.php](E:/xampp/htdocs/jbkcrm/app/Models/FeeDetailsModel.php)
- [changebatch.php](E:/xampp/htdocs/jbkcrm/app/Views/admin/changebatch.php)

## Design Principles

1. Keep admission lifecycle smaller than JBKCRM.
2. Keep payment ledger immutable and explicit.
3. Keep installment schedule derived from payment allocations, not from ad hoc form logic.
4. Keep batch assignment separate from batch master data.
5. Keep the admission detail screen compact and action-triggered.
6. Keep finance and operations together, but not cluttered.
7. Use popups for actions and dense tabs for review.

## Scope Split

### Phase 1A

- convert enquiry to admission
- admission create/edit/view
- fee structure snapshot
- first payment capture
- installment generation
- admission list workspace
- batch assignment
- admission follow-up
- hold/cancel

### Phase 1B

- payment cancellation
- additional charges
- batch change with history
- recovery queues
- printable receipt
- summary widgets and reports

## Admission Lifecycle

### Stored statuses

- `active`
- `on_hold`
- `cancelled`
- `completed`

### Working queues

- `Admissions`
- `Pending Fees`
- `Today Follow-up`
- `Missed Follow-up`
- `Batch Pending`
- `On Hold`
- `Cancelled`

Queues should be derived from dates, balances, and assignment state rather than creating too many stored statuses.

## User-Facing Navigation

### Main menu item

- `Admissions`

### Inside the admission workspace

Use queue tabs, not many top-level submenus:

1. `Admissions`
2. `Pending Fees`
3. `Today Follow-up`
4. `Missed Follow-up`
5. `Batch Pending`
6. `On Hold`
7. `Cancelled`

### Admission detail tabs

1. `Overview`
2. `Payments`
3. `Installments`
4. `Batch`
5. `Follow-ups`
6. `History`

`History` should be a compact audit surface, not mixed into operational tabs.

## Proposed Tables

### Core

#### `admissions`

Purpose:

- one row per enrolled student
- holds the operational and identity state of the admission

Suggested fields:

- `id`
- `tenant_id`
- `branch_id`
- `enquiry_id`
- `admission_number`
- `student_name`
- `email`
- `mobile`
- `whatsapp_number`
- `gender`
- `city`
- `college_id`
- `course_id`
- `assigned_user_id`
- `mode_of_class`
- `admission_date`
- `status`
- `remarks`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

#### `admission_status_logs`

Purpose:

- track lifecycle changes like hold, cancel, reopen, complete

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `old_status`
- `new_status`
- `reason`
- `remarks`
- `changed_by`
- `changed_at`

#### `admission_followups`

Purpose:

- payment recovery and operational follow-ups

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `followup_status_id`
- `communication_mode_id`
- `remarks`
- `next_followup_at`
- `is_system_generated`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

### Fee Snapshot

#### `admission_fee_snapshots`

Purpose:

- freeze the chosen fee structure for one admission

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `fee_structure_id`
- `gross_amount`
- `discount_amount`
- `net_amount`
- `paid_amount`
- `balance_amount`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

#### `admission_fee_snapshot_items`

Purpose:

- store the fee head breakdown copied from the chosen fee structure

Suggested fields:

- `id`
- `snapshot_id`
- `fee_head_name`
- `fee_head_code`
- `amount`
- `allow_discount`
- `display_order`

### Payments

#### `admission_payments`

Purpose:

- immutable payment ledger

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `receipt_number`
- `payment_kind`
- `amount`
- `payment_date`
- `payment_mode_id`
- `transaction_reference`
- `remarks`
- `received_by`
- `is_cancelled`
- `cancelled_by`
- `cancelled_at`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

#### `admission_payment_allocations`

Purpose:

- allocate one payment across installments or fee heads

Suggested fields:

- `id`
- `payment_id`
- `installment_id`
- `fee_snapshot_item_id`
- `allocated_amount`

#### `admission_additional_charges`

Purpose:

- support extra charge rows later without bloating payment ledger semantics

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `charge_name`
- `amount`
- `remarks`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

### Installments

#### `admission_installments`

Purpose:

- due schedule and recovery tracking

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `installment_number`
- `due_date`
- `due_amount`
- `paid_amount`
- `balance_amount`
- `status`
- `remarks`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

Recommended installment statuses:

- `pending`
- `partial`
- `paid`
- `overdue`
- `cancelled`

### Batches

#### `admission_batch_assignments`

Purpose:

- current active batch allocation

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `batch_id`
- `status`
- `assigned_on`
- `assigned_by`

#### `admission_batch_assignment_history`

Purpose:

- preserve every batch move

Suggested fields:

- `id`
- `tenant_id`
- `admission_id`
- `from_batch_id`
- `to_batch_id`
- `reason`
- `moved_by`
- `moved_at`

## Existing Tables Reused

- `tenant_branches`
- `users`
- `colleges`
- `courses`
- `batches`
- `payment_modes`
- `tenant_setting_values`
- shared master-data tables for follow-up status and communication mode

## Controllers

### `Admissions`

Responsibilities:

- list queues
- create admission
- edit admission
- show admission detail
- hold/cancel/complete status actions

### `AdmissionPayments`

Responsibilities:

- collect payment
- list payments on detail
- cancel payment

### `AdmissionInstallments`

Responsibilities:

- generate installment schedule
- edit installment row
- reschedule installment

### `AdmissionBatches`

Responsibilities:

- assign batch
- change batch
- list current and historical batch movement

### `AdmissionFollowups`

Responsibilities:

- add follow-up
- edit follow-up
- delete follow-up

## Services

### `AdmissionService`

- admission creation from enquiry
- update core admission fields
- hold/cancel/complete actions

### `AdmissionFeeService`

- take fee structure snapshot
- compute gross/net/balance figures

### `AdmissionPaymentService`

- create payment ledger rows
- allocate payment
- update fee summary snapshot
- cancel payment cleanly

### `AdmissionInstallmentService`

- generate initial schedule
- update schedule after payment allocation if needed
- reschedule installment rows

### `AdmissionBatchService`

- assign initial batch
- change batch
- write batch history

### `AdmissionFollowupService`

- add/edit/delete follow-up
- compute follow-up queues

### `AdmissionQueueService`

- build queue filters and list data for:
  - admissions
  - pending fees
  - follow-up today
  - missed follow-up
  - batch pending
  - on hold
  - cancelled

## Models

Create these models:

- `AdmissionModel`
- `AdmissionStatusLogModel`
- `AdmissionFollowupModel`
- `AdmissionFeeSnapshotModel`
- `AdmissionFeeSnapshotItemModel`
- `AdmissionPaymentModel`
- `AdmissionPaymentAllocationModel`
- `AdmissionAdditionalChargeModel`
- `AdmissionInstallmentModel`
- `AdmissionBatchAssignmentModel`
- `AdmissionBatchAssignmentHistoryModel`

## Proposed Migration Filenames

Build in this order:

1. `2026-04-19-090000_CreateAdmissions.php`
2. `2026-04-19-090100_CreateAdmissionFeeSnapshots.php`
3. `2026-04-19-090200_CreateAdmissionFeeSnapshotItems.php`
4. `2026-04-19-090300_CreateAdmissionInstallments.php`
5. `2026-04-19-090400_CreateAdmissionPayments.php`
6. `2026-04-19-090500_CreateAdmissionPaymentAllocations.php`
7. `2026-04-19-090600_CreateAdmissionAdditionalCharges.php`
8. `2026-04-19-090700_CreateAdmissionBatchAssignments.php`
9. `2026-04-19-090800_CreateAdmissionBatchAssignmentHistory.php`
10. `2026-04-19-090900_CreateAdmissionFollowups.php`
11. `2026-04-19-091000_CreateAdmissionStatusLogs.php`
12. `2026-04-19-091100_AddAdmissionPrivileges.php`
13. `2026-04-19-091200_AddAdmissionAuditTriggers.php`

## Proposed Route Map

### Admission workspace

- `GET /admissions`
- `GET /admissions/create`
- `POST /admissions`
- `GET /admissions/{id}`
- `GET /admissions/{id}/edit`
- `POST /admissions/{id}`

### Status actions

- `POST /admissions/{id}/hold`
- `POST /admissions/{id}/cancel`
- `POST /admissions/{id}/complete`

### Payments

- `POST /admissions/{id}/payments`
- `POST /admissions/{id}/payments/{paymentId}/cancel`

### Installments

- `POST /admissions/{id}/installments/generate`
- `GET /admissions/{id}/installments/{installmentId}/edit`
- `POST /admissions/{id}/installments/{installmentId}`

### Batch

- `POST /admissions/{id}/batch`
- `POST /admissions/{id}/batch/change`

### Follow-ups

- `POST /admissions/{id}/followups`
- `GET /admissions/{id}/followups/{followupId}/edit`
- `POST /admissions/{id}/followups/{followupId}`
- `POST /admissions/{id}/followups/{followupId}/delete`

## Privileges

Phase 1 privileges should include:

- `admissions.view`
- `admissions.create`
- `admissions.edit`
- `admissions.hold`
- `admissions.cancel`
- `admissions.complete`
- `admissions.view_history`
- `admissions.view_mobile_number`
- `admissions.collect_payment`
- `admissions.cancel_payment`
- `admissions.edit_installment`
- `admissions.assign_batch`
- `admissions.change_batch`
- `admissions.followups.view`
- `admissions.followups.create`
- `admissions.followups.edit`
- `admissions.followups.delete`

## Screen-by-Screen Deliverables

### 1. Admissions list

Deliver:

- queue tabs
- compact table
- filters
- row click opens admission detail
- add admission trigger

Recommended columns:

- Name
- Mobile
- Course
- College
- Branch
- Assigned to
- Status
- Admission date
- Balance
- Actions

### 2. Create admission popup

Deliver:

- enquiry reference
- core student/admission details
- fee structure selection
- discount entry
- first payment section
- installment generation section
- initial batch assignment section

### 3. Admission detail

Deliver:

- left summary rail
- top finance strip
- tabs:
  - Overview
  - Payments
  - Installments
  - Batch
  - Follow-ups
  - History

### 4. Collect payment popup

Deliver:

- payment amount
- payment date
- payment mode
- reference id
- remarks
- allocation preview if needed

### 5. Installment tab

Deliver:

- due date
- due amount
- paid amount
- balance
- status
- row edit action

### 6. Batch tab

Deliver:

- current batch
- assign batch action
- change batch action
- batch history list

### 7. Follow-up tab

Deliver:

- follow-up timeline
- add follow-up action
- edit/delete follow-up when allowed

### 8. History tab

Deliver:

- compact audit events
- status changes
- payment cancellations
- batch changes
- key operational edits

## Audit Trigger Plan

Trigger-based audit should capture:

- admission created
- admission updated
- admission held
- admission cancelled
- admission completed
- payment created
- payment cancelled
- installment edited
- batch assigned
- batch changed
- follow-up added
- follow-up edited
- follow-up deleted

Audit should be:

- automatic
- written to audit log tables
- visible in a separate `History` tab
- not mixed into the main payment/follow-up operational views

## Phase 1A Execution Checklist

### Database

- [ ] create admissions core table
- [ ] create fee snapshot tables
- [ ] create installment table
- [ ] create payment ledger tables
- [ ] create batch assignment tables
- [ ] create follow-up table
- [ ] create status log table
- [ ] add admission privileges
- [ ] add admission audit triggers

### Backend

- [ ] add models
- [ ] add services
- [ ] add routes
- [ ] build admission creation from enquiry
- [ ] build fee snapshot logic
- [ ] build initial payment logic
- [ ] build installment generation logic
- [ ] build batch assign logic
- [ ] build follow-up logic

### Frontend

- [ ] admissions list
- [ ] create admission popup
- [ ] admission detail
- [ ] collect payment popup
- [ ] installments tab
- [ ] batch tab
- [ ] follow-up tab
- [ ] history tab

### Validation

- [ ] admission create works from enquiry
- [ ] first payment updates finance snapshot
- [ ] installment rows generate correctly
- [ ] batch assign works
- [ ] follow-up queues work
- [ ] hold/cancel flows work

## Phase 1B Checklist

- [ ] payment cancellation
- [ ] additional charges
- [ ] payment recovery queues
- [ ] receipt print/download
- [ ] batch transfer history refinement
- [ ] finance summary widgets
- [ ] compact reports and exports

## What We Will Not Copy From JBKCRM

- too many admission tabs
- too many mixed action buttons on one screen
- mutable fee/payment summary logic without a clean ledger
- crowded detail pages
- overloaded and overlapping status meanings
- giant all-in-one forms always open on screen

## Build Recommendation

Implementation should begin with:

1. migrations
2. models
3. services
4. routes
5. create admission flow
6. admission list
7. admission detail
8. payments
9. installments
10. batch assignment
11. follow-ups

That order gives the quickest stable operational path.
