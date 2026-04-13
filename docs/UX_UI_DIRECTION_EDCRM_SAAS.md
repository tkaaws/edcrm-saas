# EDCRM SaaS UI/UX Direction

## 1. Purpose

This document defines the product experience direction for EDCRM SaaS after the Phase 1A operational foundation is stable.

It is not a visual mockup file.
It is the experience standard that should guide:

- layout decisions
- interaction patterns
- mobile responsiveness
- module design consistency
- information density
- usability for worldwide institute teams

---

## 2. Product Experience Goals

The UI should feel:

- fast
- clear
- trustworthy
- operationally dense without feeling cluttered
- easy for first-time users
- efficient for experienced daily operators

The product should support:

- desktop-heavy admin usage
- mobile access for quick actions and approvals
- branch-level operators with limited time and attention
- tenant owners who need summary visibility
- future global usage across currencies, timezones, and locales

---

## 3. Design Principles

### 3.1 Operational first

The product should open into a working surface, not a marketing surface.

Each screen must help the user do one of these clearly:

- inspect
- decide
- create
- update
- approve
- follow up
- export

### 3.2 Clarity before decoration

Visual polish matters, but operational clarity matters more.

Priority order:

1. legibility
2. hierarchy
3. speed of understanding
4. consistency
5. polish

### 3.3 Progressive complexity

Small institutes and large institute groups must both feel comfortable.

The UI should reveal complexity gradually:

- summary first
- detail on demand
- advanced controls when needed

### 3.4 Global readiness

The product must support:

- timezone-safe date display
- currency-safe amount display
- locale-aware formatting
- long names and long institute labels
- responsive layouts across different text lengths

---

## 4. Layout System

### 4.1 Primary application frame

The product should use:

- left navigation on desktop
- top summary and action area
- content-first central workspace
- compact mobile navigation pattern

### 4.2 Module page structure

Recommended structure for each module:

1. page title and context
2. summary metrics or filters
3. primary action row
4. main data surface
5. side insights or secondary panels only where justified

### 4.3 Density model

The application should support moderate to high density, but not cramped density.

Rules:

- forms should breathe
- tables should be scannable
- lists should show status quickly
- actions should be visible without noise

---

## 5. Navigation Model

Primary navigation should eventually include:

- Dashboard
- Enquiries
- Followups
- Admissions
- Fees
- Students
- Service
- Placement
- Reports
- Users
- Branches
- Roles
- Settings
- Billing

Secondary navigation should be contextual inside each module, not global.

---

## 6. Component Standards

### 6.1 Tables

Tables should support:

- sticky header on larger data views
- quick filters
- status badges
- row-level actions
- bulk actions where operationally needed
- responsive collapse strategy on mobile

### 6.2 Forms

Forms should support:

- grouped sections
- clear labels
- inline validation
- helper text only where useful
- predictable action placement
- safe save/cancel patterns

### 6.3 Status indicators

Use status colors sparingly and consistently for:

- active
- inactive
- pending
- warning
- blocked
- success

### 6.4 Cards and summary panels

Cards should be used for:

- metrics
- grouped actions
- summary states

Do not over-card the application.
Main data areas should still feel like a working system, not a dashboard toy.

---

## 7. Mobile and Responsive Direction

The mobile experience should not be a squeezed desktop.

Rules:

- actions must remain reachable with one hand
- tables must transform into card or stacked row patterns
- filters should collapse into drawers or sheets
- long forms should become sectioned mobile steps where needed
- critical create and update flows must remain usable on mobile

---

## 8. Accessibility and Usability Standards

Minimum standards:

- keyboard-usable forms
- visible focus states
- readable contrast
- non-color-only status communication
- clear error states
- meaningful empty states
- loading states for all slow operations

---

## 9. Visual Direction

The product should feel:

- modern
- clean
- calm
- operational

It should avoid:

- overuse of gradients
- purple-heavy generic SaaS visuals
- oversized marketing-style cards
- decorative clutter
- low-contrast muted-on-muted text

Recommended visual character:

- strong typography
- restrained color palette
- precise spacing
- crisp tables and forms
- subtle state color usage

---

## 10. Module-by-Module UX Priority

### Phase 1A operational UX priority

1. Users
2. Branches
3. Roles
4. Settings

### Phase 2 workflow UX priority

1. Enquiries
2. Followups
3. Admissions
4. Fees
5. Students
6. Service
7. Placement

### Phase 3 experience extensions

1. Billing self-service
2. Student portal
3. advanced reporting
4. communication center

---

## 11. Delivery Recommendation

Recommended delivery sequence:

1. finish functional module foundations on current shell
2. stabilize routes, permissions, and data flows
3. design a reusable high-fidelity component system
4. redesign module by module, starting with Users and Enquiries
5. optimize mobile behavior in each module before broad rollout

This keeps the product moving without blocking delivery on visual redesign too early.
