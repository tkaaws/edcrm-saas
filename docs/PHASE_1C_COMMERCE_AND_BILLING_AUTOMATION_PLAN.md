# EDCRM SaaS Phase 1C Commerce and Billing Automation Plan

## 1. Executive Summary

This document defines the Phase 1C plan for the commercial layer of `edcrm-saas`.

Phase 1C begins only after:

- Phase 1A multi-tenant foundation is stable
- Phase 1B billing catalog, entitlements, limits, and subscription restriction engine are stable

Phase 1C is the layer that turns the subscription model into an operational revenue system.

It covers:

- self-service signup
- payment gateway integration
- plan purchase and activation
- module add-on purchase flow
- proration rules
- invoicing
- renewal automation
- payment failure handling
- grace and suspension automation
- cancellation and export workflow

This is not the foundation layer. It is the commerce execution layer built on top of the Phase 1A/1B architecture.

---

## 2. Objectives

By the end of Phase 1C, the platform should support:

- new tenant signup without manual admin intervention
- trial creation and trial expiry handling
- paid plan activation through payment gateway
- module add-on purchase
- monthly and yearly billing cycles in live commerce
- invoice generation
- payment event processing
- automated renewal and failure handling
- automatic grace transition
- automatic suspension transition
- owner-facing billing and upgrade UX
- platform-admin visibility into subscription revenue activity

---

## 3. Product Scope

### 3.1 In scope

- public pricing page
- self-service signup flow
- tenant provisioning after signup
- trial management
- payment gateway integration
- checkout flow for plan purchase
- add-on purchase flow
- invoice generation and storage
- billing event processing
- cron or scheduled automation jobs
- billing reminders
- cancellation flow
- limited export window after cancellation

### 3.2 Out of scope for Phase 1C

- tax engine sophistication
- coupon and promotion engine
- multi-gateway orchestration
- marketplace for third-party apps
- custom enterprise quoting workflow automation
- full CPQ-style contract management
- advanced revenue recognition/accounting integration

---

## 4. Commercial Model Assumptions

Phase 1C inherits these billing assumptions from Phase 1B:

- one active subscription per tenant
- tenant buys a base plan
- tenant may buy module add-ons
- tenant may be constrained by active-user and branch limits
- monthly and yearly cycles are supported
- grace period exists
- suspension is controlled, not destructive

### 4.1 Commercial packaging

Recommended product packaging:

- base subscription
- module add-ons
- capacity tiers
- yearly discount

Recommended examples:

- Starter
- Basic
- Growth
- Scale
- Enterprise

Recommended paid add-ons:

- admissions
- service_tickets
- placement
- batch_management
- whatsapp
- advanced_reports
- student_portal

---

## 5. Customer Commerce Flows

### 5.1 Flow A - Assisted sales activation

This flow is important early because many institute deals may still be sales-assisted.

Steps:

1. platform admin creates tenant
2. platform admin assigns plan
3. platform admin issues invoice or payment link
4. payment is confirmed
5. subscription becomes active
6. tenant owner receives onboarding access

This flow should remain available even after self-service launch.

### 5.2 Flow B - Self-service signup

Steps:

1. institute owner opens pricing page
2. selects billing cycle and plan
3. enters tenant/company details
4. tenant account is provisioned in `draft` or `trial`
5. payment is initiated
6. on successful payment, tenant becomes `active`
7. tenant owner receives login/setup instructions

### 5.3 Flow C - Add module

Steps:

1. tenant owner opens billing/settings area
2. selects a new module add-on
3. system calculates amount due
4. payment is completed
5. entitlement becomes active
6. menus and routes become available

### 5.4 Flow D - Renewal

Steps:

1. scheduled billing cycle arrives
2. payment attempt occurs
3. success extends term
4. failure starts grace flow

### 5.5 Flow E - Cancellation

Steps:

1. tenant owner requests cancellation
2. subscription marked `cancelled`
3. access remains until paid term ends
4. after expiry and grace, tenant becomes suspended
5. export window is offered
6. tenant becomes fully inactive after retention policy ends

---

## 6. Commerce Components

### 6.1 Public pricing layer

Needs:

- clear monthly/yearly toggle
- plan comparison
- module add-on visibility
- enterprise contact path
- CTA for signup or contact sales

### 6.2 Checkout and payment layer

Needs:

- payment order creation
- payment confirmation
- secure gateway callback/webhook handling
- idempotent payment processing

### 6.3 Billing document layer

Needs:

- invoice creation
- invoice numbering
- invoice storage
- invoice PDF download
- payment receipt reference

### 6.4 Automation layer

Needs:

- trial reminder automation
- renewal automation
- payment failure handling
- grace transition
- suspension transition
- cancellation/export timeline handling

### 6.5 Billing visibility layer

Platform admin:

- active subscriptions
- expiring subscriptions
- failed payments
- grace tenants
- suspended tenants
- revenue trends later

Tenant owner:

- current plan
- enabled modules
- next billing date
- invoice history
- payment history
- upgrade options

---

## 7. Payment Gateway Strategy

### 7.1 Recommended primary gateway

Recommended primary gateway:

- **Razorpay**

Reason:

- suitable for current market context
- easier for India-first commercial rollout
- supports recurring/subscription patterns

### 7.2 Secondary future gateway

Future secondary option:

- **Stripe**

Reason:

- better for broader international self-service later

### 7.3 Gateway abstraction rule

Even if Razorpay is chosen first, integration should be written behind an internal billing gateway abstraction.

Suggested internal contract:

- create payment intent/order
- create subscription charge request
- capture success/failure event
- fetch payment metadata
- normalize external payloads into internal billing events

This avoids gateway lock-in.

---

## 8. Proration Rules

Proration should be defined clearly before implementation.

### 8.1 Add-on purchase during active cycle

If a tenant adds a paid module mid-cycle:

- charge only for remaining period until next renewal

### 8.2 Capacity upgrade during active cycle

If tenant moves to a higher user tier mid-cycle:

- charge only the prorated difference between tiers

### 8.3 Capacity downgrade

Recommended rule:

- downgrade takes effect at next renewal
- no mid-cycle refund in v1

### 8.4 Module removal

Recommended rule:

- removal takes effect at next renewal
- no mid-cycle refund in v1

### 8.5 Annual plan proration

For yearly plans:

- prorate by remaining days in annual period

### 8.6 Rounding

All proration calculations must:

- round to 2 decimal places
- use currency-safe arithmetic

---

## 9. Invoicing and Records

### 9.1 Invoice requirements

Each successful commercial event should create an invoice or billing document when applicable:

- new subscription purchase
- renewal
- add-on purchase
- manual billing adjustment if supported later

### 9.2 Invoice fields

Invoices should include:

- invoice number
- tenant
- billing customer
- issue date
- due/paid date
- currency
- line items
- total
- status
- payment reference

### 9.3 Numbering

Recommended v1:

- sequential invoice number per tenant or globally generated stable number

### 9.4 Payment records

Separate payment capture records should store:

- gateway payment ID
- invoice link
- amount
- status
- raw event reference

---

## 10. Trial, Grace, and Suspension Automation

### 10.1 Trial model

Recommended default:

- 14-day free trial

Trial reminders:

- day 7
- day 12
- day 14 expiry warning

### 10.2 Grace model

Recommended default:

- 7-day grace after failed renewal or unpaid expiry

### 10.3 Suspension model

After grace ends:

- owner/admin retain billing access
- operational users blocked
- business write operations blocked

### 10.4 Export window

Recommended post-cancellation access:

- 30-day export window for owner/admin

This can be implemented after core suspension if needed, but should be designed now.

---

## 11. Scheduled Jobs / Automation

Phase 1C requires scheduled automation.

Suggested jobs:

- trial reminder job
- renewal due job
- payment failure follow-up job
- grace-to-suspended transition job
- cancelled-tenant export-window expiry job

Each job must be:

- idempotent
- auditable
- safe to retry

---

## 12. Data and Event Requirements

### 12.1 Event logging

Every billing lifecycle event should be written to `billing_events`.

Examples:

- subscription created
- payment initiated
- payment captured
- payment failed
- trial expired
- grace started
- tenant suspended
- cancellation requested
- export window ended

### 12.2 Idempotency

Webhook/event handlers must be idempotent.

Receiving the same payment callback twice must not:

- create duplicate payment rows
- create duplicate invoices
- activate subscription twice

---

## 13. Platform Admin Requirements

Platform admin should be able to:

- create and edit plans
- assign subscriptions manually
- inspect billing events
- inspect failed payments
- inspect grace tenants
- inspect suspended tenants
- trigger manual recovery or extension where policy allows

Future analytics can include:

- MRR
- ARR
- churn
- active subscriptions by plan

These analytics are not mandatory for initial 1C delivery.

---

## 14. Tenant Owner Requirements

Tenant owner should be able to:

- view current plan
- view enabled modules
- view next renewal date
- view current billing status
- view invoices and payment history
- request upgrade
- add modules
- request cancellation

Tenant owner should not be able to:

- break system policy by bypassing payment
- enable entitlements not purchased

---

## 15. Test Plan

Must-test areas:

- signup flow
- trial creation
- payment success path
- payment failure path
- renewal path
- grace transition
- suspension transition
- add-on purchase path
- proration calculations
- invoice generation
- webhook idempotency
- cancellation path

Key scenarios:

- successful self-service signup activates tenant
- failed payment triggers grace
- grace expiry triggers suspension
- add-on purchase enables feature without manual intervention
- duplicate webhook does not duplicate billing records

---

## 16. Risks To Avoid

Do not allow these anti-patterns:

- payment gateway logic spread across controllers
- non-idempotent webhook handlers
- plan logic hardcoded in UI only
- invoices created only in memory or not persisted
- grace/suspension handled manually by admin only
- pricing assumptions embedded in module code

---

## 17. Dependencies On Earlier Phases

Phase 1C must not begin until these are stable:

- tenant schema
- branch schema
- user and privilege model
- billing catalog
- plan entitlements
- plan limits
- subscription status machine
- restriction engine

Phase 1C depends on Phase 1A and 1B. It should not define or override them.

---

## 18. Acceptance Criteria

Phase 1C is complete when:

- a tenant can sign up through a public flow
- a trial can be created automatically
- a paid plan can be activated through gateway success
- failed payment moves tenant into grace
- grace expiry moves tenant into suspended automatically
- invoices are generated and visible
- tenant owner can view billing details and upgrade options
- add-on purchase updates entitlements correctly
- billing jobs run safely and repeatedly

---

## 19. Recommendation For Next Step

After this document is reviewed:

1. freeze payment gateway choice
2. freeze trial and grace policy
3. freeze proration rules
4. freeze invoice numbering strategy
5. decide whether 1C begins immediately after 1B or later

Only then should commerce implementation begin.
