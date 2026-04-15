# EDCRM SaaS Master Data Implementation Blueprint

## 1. Purpose

This blueprint defines the implementation contract for EDCRM master data.

It covers:

- table-by-table schema
- migration order
- model and service responsibilities
- seed order
- first-release master-data catalog
- platform and tenant management screens
- rollout order before Enquiry implementation

The target model is:

- platform provides standard values
- tenant can add custom values where allowed
- effective dropdowns use a union of platform + tenant values
- tenant can optionally hide selected platform values where the type allows it

---

## 2. Scope Model

Each master data value belongs to one of these scopes:

- `platform`
- `tenant`

For any tenant, the effective list is:

1. active platform values
2. plus active tenant values for that tenant
3. minus platform values hidden by tenant override

This model gives standardization and flexibility without duplicating catalogs for every tenant.

---

## 3. Migration Order

Create the schema in this order:

1. `master_data_types`
2. `master_data_values`
3. `tenant_master_data_overrides`

Recommended migration file names:

1. `2026-04-15-100000_CreateMasterDataTypes.php`
2. `2026-04-15-101000_CreateMasterDataValues.php`
3. `2026-04-15-102000_CreateTenantMasterDataOverrides.php`

---

## 4. Table Definitions

### 4.1 `master_data_types`

Purpose:
- defines each master-data catalog group

Columns:
- `id` bigint unsigned PK auto_increment
- `code` varchar(100) not null
- `name` varchar(150) not null
- `description` text null
- `module_code` varchar(100) not null
- `status` enum('active','inactive') default 'active'
- `allow_platform_entries` tinyint(1) default 1
- `allow_tenant_entries` tinyint(1) default 1
- `allow_tenant_hide_platform_values` tinyint(1) default 0
- `strict_reporting_catalog` tinyint(1) default 0
- `supports_hierarchy` tinyint(1) default 0
- `sort_order` int default 0
- `created_at` datetime null
- `updated_at` datetime null

Indexes:
- unique: `code`
- index: `module_code`
- index: `status`

Notes:
- `strict_reporting_catalog = 1` means reporting should prefer globally standardized values
- `supports_hierarchy = 1` means values may use parent-child relationships

---

### 4.2 `master_data_values`

Purpose:
- stores actual master values

Columns:
- `id` bigint unsigned PK auto_increment
- `type_id` bigint unsigned not null
- `scope_type` enum('platform','tenant') not null
- `tenant_id` bigint unsigned null
- `parent_value_id` bigint unsigned null
- `code` varchar(100) not null
- `label` varchar(150) not null
- `short_label` varchar(100) null
- `description` text null
- `color_code` varchar(20) null
- `icon_name` varchar(100) null
- `sort_order` int default 0
- `is_system` tinyint(1) default 0
- `status` enum('active','inactive') default 'active'
- `metadata_json` json null
- `created_by` bigint unsigned null
- `updated_by` bigint unsigned null
- `created_at` datetime null
- `updated_at` datetime null

Indexes:
- unique: (`type_id`, `scope_type`, `tenant_id`, `code`)
- index: (`type_id`, `status`)
- index: (`tenant_id`, `status`)
- index: `parent_value_id`

Foreign keys:
- `type_id -> master_data_types.id`
- `tenant_id -> tenants.id`
- `parent_value_id -> master_data_values.id`

Rules:
- if `scope_type = platform`, `tenant_id` must be null
- if `scope_type = tenant`, `tenant_id` must be set
- `parent_value_id` should normally point to a value within the same type

Notes:
- `metadata_json` is for type-specific extensions like course duration, tags, delivery mode, or reporting hints
- `is_system = 1` means protected platform value that should not be hard-deleted casually

---

### 4.3 `tenant_master_data_overrides`

Purpose:
- lets a tenant hide or lightly override platform values

Columns:
- `id` bigint unsigned PK auto_increment
- `tenant_id` bigint unsigned not null
- `master_data_value_id` bigint unsigned not null
- `is_visible` tinyint(1) default 1
- `sort_order_override` int null
- `label_override` varchar(150) null
- `updated_by` bigint unsigned null
- `updated_at` datetime null

Indexes:
- unique: (`tenant_id`, `master_data_value_id`)

Foreign keys:
- `tenant_id -> tenants.id`
- `master_data_value_id -> master_data_values.id`

Rules:
- only valid for platform-scoped values
- a tenant cannot override another tenant's custom value

Notes:
- first release only needs `is_visible`
- `sort_order_override` and `label_override` can remain unused until needed

---

## 5. Model Checklist

Create these models:

### 5.1 `MasterDataTypeModel`
Responsibilities:
- fetch active types
- fetch type by code
- platform admin list/filter

### 5.2 `MasterDataValueModel`
Responsibilities:
- fetch platform values by type
- fetch tenant values by type and tenant
- fetch children for hierarchical types
- create/update platform and tenant values

### 5.3 `TenantMasterDataOverrideModel`
Responsibilities:
- upsert tenant hide/show rows
- fetch override map for a tenant and type

---

## 6. Service Contract

Create:
- `MasterDataService`

Responsibilities:
- resolve effective values for a tenant
- hide/show platform values for a tenant
- create tenant custom values safely
- enforce type policy rules

Suggested methods:
- `getTypeByCode(string $typeCode): ?object`
- `getEffectiveValues(string $typeCode, int $tenantId): array`
- `getPlatformValues(string $typeCode): array`
- `getTenantValues(string $typeCode, int $tenantId): array`
- `getEffectiveHierarchy(string $typeCode, int $tenantId): array`
- `createTenantValue(string $typeCode, int $tenantId, array $payload): int`
- `updateTenantValue(int $valueId, int $tenantId, array $payload): bool`
- `hidePlatformValue(int $tenantId, int $valueId): void`
- `showPlatformValue(int $tenantId, int $valueId): void`

Resolution logic:

1. get type by code
2. load active platform values for type
3. load active tenant values for type and tenant
4. load tenant overrides for platform values
5. filter hidden platform rows
6. merge and sort by effective order

Controllers and forms should never compose this union manually.

---

## 7. Validation Rules

### 7.1 Type validation
- `code` required
- `code` unique
- `module_code` required
- `status` valid

### 7.2 Value validation
- `type_id` valid
- `code` required
- `label` required
- `code` unique within type + scope + tenant
- platform values must have null `tenant_id`
- tenant values must have valid `tenant_id`
- parent value must exist when provided

### 7.3 Override validation
- tenant exists
- master value exists
- master value scope must be `platform`
- unique row per tenant + value

---

## 8. Seed Order

Run seeders in this order:

1. `MasterDataTypesSeeder`
2. `MasterDataValuesSeeder`
3. optional local/demo tenant override seed

Recommended `DatabaseSeeder` order:

1. billing and access seeders
2. settings/access foundation seeders
3. `MasterDataTypesSeeder`
4. `MasterDataValuesSeeder`
5. demo tenant data only where appropriate

---

## 9. First-Release Master Types

These are the first catalog types to implement before Enquiry.

### 9.1 `enquiry_source`
- module: `enquiries`
- allow tenant entries: yes
- allow tenant hide platform values: yes
- strict reporting catalog: no

### 9.2 `lead_qualification`
- module: `enquiries`
- allow tenant entries: yes
- allow tenant hide platform values: yes
- strict reporting catalog: no

### 9.3 `followup_status`
- module: `enquiries`
- allow tenant entries: yes
- allow tenant hide platform values: yes

### 9.4 `mode_of_communication`
- module: `enquiries`
- allow tenant entries: limited yes
- allow tenant hide platform values: yes

### 9.5 `enquiry_lost_reason`
- module: `enquiries`
- allow tenant entries: yes
- allow tenant hide platform values: yes

### 9.6 `enquiry_closure_reason`
- module: `enquiries`
- allow tenant entries: limited yes
- allow tenant hide platform values: no

### 9.7 `purpose_category`
- module: `enquiries`
- allow tenant entries: yes
- allow tenant hide platform values: yes

### 9.8 `course`
- module: `crm_core`
- allow tenant entries: yes
- allow tenant hide platform values: no
- strict reporting catalog: no

---

## 10. Seed Payload Draft

### 10.1 `MasterDataTypesSeeder`

Insert these rows:

1. `enquiry_source`
2. `lead_qualification`
3. `followup_status`
4. `mode_of_communication`
5. `enquiry_lost_reason`
6. `enquiry_closure_reason`
7. `purpose_category`
8. `course`

Each should include:
- `code`
- `name`
- `module_code`
- `allow_tenant_entries`
- `allow_tenant_hide_platform_values`
- `strict_reporting_catalog`
- `sort_order`

### 10.2 `MasterDataValuesSeeder`

#### Type: `enquiry_source`
- `walk_in` => Walk-in
- `website` => Website
- `facebook` => Facebook
- `google_ads` => Google Ads
- `referral` => Referral
- `whatsapp` => WhatsApp
- `phone_call` => Phone Call
- `webinar` => Webinar
- `event` => Event

#### Type: `lead_qualification`
- `hot` => Hot
- `warm` => Warm
- `cold` => Cold
- `not_interested` => Not Interested
- `follow_up_later` => Follow Up Later

#### Type: `followup_status`
- `connected` => Connected
- `not_connected` => Not Connected
- `callback_requested` => Callback Requested
- `interested` => Interested
- `follow_up_required` => Follow Up Required
- `visit_scheduled` => Visit Scheduled

#### Type: `mode_of_communication`
- `phone_call` => Phone Call
- `whatsapp` => WhatsApp
- `email` => Email
- `sms` => SMS
- `in_person` => In Person
- `video_call` => Video Call

#### Type: `enquiry_lost_reason`
- `not_interested` => Not Interested
- `budget_issue` => Budget Issue
- `joined_other_institute` => Joined Other Institute
- `no_response` => No Response
- `location_issue` => Location Issue
- `timing_issue` => Timing Issue

#### Type: `enquiry_closure_reason`
- `converted_to_admission` => Converted to Admission
- `duplicate` => Duplicate
- `expired` => Expired
- `manually_closed` => Manually Closed

#### Type: `purpose_category`
- `course_enquiry` => Course Enquiry
- `demo_request` => Demo Request
- `fee_enquiry` => Fee Enquiry
- `support_request` => Support Request

#### Type: `course`
- `java_full_stack` => Java Full Stack
- `python_full_stack` => Python Full Stack
- `data_analytics` => Data Analytics
- `software_testing` => Software Testing
- `aws_devops` => AWS DevOps

Recommended `course.metadata_json` examples:
- `duration_days`
- `delivery_mode`
- `active_for_enquiry`
- `active_for_admission`

---

## 11. Future Master Types From JBKCRM Reference

These should be scheduled next, not necessarily built in release 1:

- `qualification`
- `qualification_stream`
- `course_category`
- `batch_category`
- `payment_mode`
- `skill`
- `placement_status`
- `hiring_status`
- `university`
- `college`
- `state`
- `city`
- `ticket_category`
- `ticket_priority`

---

## 12. Platform UI Plan

Path:
- `Platform > Master Data`

Screens:

### 12.1 Types List
Columns:
- name
- code
- module
- status
- tenant entries allowed
- hide platform values allowed
- actions

Actions:
- create type
- edit type
- activate/deactivate type

### 12.2 Values List
Filter by:
- type
- status

Columns:
- label
- code
- scope
- parent
- status
- sort order
- actions

Actions:
- create platform value
- edit platform value
- activate/deactivate value

---

## 13. Tenant UI Plan

Path:
- `Settings > Master Data`

Recommended sections:
- Enquiry
- Academics
- Finance
- Placement
- General

For first release, tenant UI only needs the first Enquiry-related types plus `course`.

### 13.1 Effective Values Screen
Columns:
- label
- code
- source (`platform` / `tenant`)
- status
- visible
- actions

Actions:
- add tenant custom value
- edit tenant custom value
- hide platform value
- restore platform value

Rules:
- platform rows cannot be edited directly by tenant
- tenant rows can be edited/deactivated by tenant if type allows entries

---

## 14. Privileges

Add these privileges:

### Platform
- `platform.master_data.view`
- `platform.master_data.manage`

### Tenant
- `settings.master_data.view`
- `settings.master_data.manage`

For now, one tenant master-data manage privilege is enough.

---

## 15. Implementation Checklist

### Phase M1 - schema and service
1. create `master_data_types` migration
2. create `master_data_values` migration
3. create `tenant_master_data_overrides` migration
4. create models
5. create `MasterDataService`

### Phase M2 - seed baseline catalog
6. add `MasterDataTypesSeeder`
7. add `MasterDataValuesSeeder`
8. wire seeders into local/demo database seed flow if appropriate

### Phase M3 - admin screens
9. build platform type management screen
10. build platform value management screen
11. build tenant effective master-data screen
12. build tenant custom value create/edit flow
13. build tenant hide/show platform value flow

### Phase M4 - module consumption
14. use master-data service in Enquiry form dropdowns
15. use master-data service in Enquiry filters and reports
16. add tests for effective union behavior

---

## 16. Definition of Done

This blueprint is implemented successfully when:

- platform can create and manage master-data types
- platform can create and manage platform values
- tenant can see effective values for allowed types
- tenant can add custom values where allowed
- tenant can hide platform values where allowed
- Enquiry forms load sources, qualification, follow-up status, communication mode, reasons, purpose, and course from the master-data service
- no controller builds union lists manually
- automated tests cover effective union behavior and override logic

---

## 17. Recommended First Build Scope

Build only this first set:

1. `enquiry_source`
2. `lead_qualification`
3. `followup_status`
4. `mode_of_communication`
5. `enquiry_lost_reason`
6. `enquiry_closure_reason`
7. `purpose_category`
8. `course`

That is enough to support proper Enquiry implementation without overbuilding.
