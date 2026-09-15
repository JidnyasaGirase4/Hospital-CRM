# Hospital CRM — Backend (Laravel REST API)

A REST API backend for a Hospital CRM / Hospital Management System, built with Laravel + MySQL. The frontend (HTML/CSS/Vanilla JS) is a separate project and is **not** served from here — this repo exposes versioned JSON APIs only, under `/api/v1/`.

Everything in the system is designed to connect back to a single source of truth: **Patient 360°**.

## Tech Stack

- PHP 8.2, Laravel 11
- MySQL 8 (InnoDB, `utf8mb4`)
- Laravel Sanctum (token-based API auth)
- Custom RBAC (roles / permissions / pivot tables — no third-party ACL package)
- PHPUnit / Laravel feature tests

## Architecture Principles

- **Thin controllers.** Controllers validate (via Form Requests), delegate to a Service class, and return an API Resource. No business logic in controllers.
- **Services own business logic.** `PatientService`, `AppointmentService`, `BillingService`, `PharmacyService`, etc. Anything involving multiple models, stock/ledger math, or multi-step workflows lives in a service, wrapped in a DB transaction where it mutates more than one table.
- **Policies own authorization.** Every protected resource has a Policy; controllers/middleware call `$this->authorize()`. Role/permission checks are never hardcoded inline in controllers.
- **API Resources own response shape.** Models never serialize directly to clients.
- **Money is `decimal`, never `float`.** All financial columns use `decimal(12,2)` (or similar) and PHP `BCMath`/string-safe arithmetic where precision matters.
- **Every write that must be atomic runs inside `DB::transaction()`** — stock deduction, billing, bed allocation, payments/refunds.
- **Audit log is a first-class concern**, not an afterthought — sensitive actions are recorded via an `AuditLogService` / model observers, not sprinkled ad hoc.
- **Soft deletes** on clinical/financial records where retaining history matters (patients, bills, prescriptions, admissions, etc.); hard deletes only for genuinely transient data.

## API Conventions

Base path: `/api/v1/...`

**Success**
```json
{ "success": true, "message": "Patient created successfully", "data": {} }
```

**Validation / error**
```json
{ "success": false, "message": "Validation failed", "data": null, "errors": {} }
```

All list endpoints are paginated, filterable, and return consistent metadata. Every protected endpoint requires a Sanctum bearer token and is gated by a Policy/permission check.

## Local Setup

```bash
composer install
cp .env.example .env      # already done in this repo; adjust DB credentials if needed
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Default DB: MySQL database `hospital_crm` on `127.0.0.1:3306`, user `root` (XAMPP defaults — change in `.env` for your environment).

Seeded roles and a Super Admin user are created by `database/seeders/DatabaseSeeder.php` (see Phase 1).

## Modules

Auth · Users · Roles · Permissions · Patients · Patient 360° · Appointments · OPD · Consultations · Diagnoses · Prescriptions · Pharmacy · Laboratory · Radiology · IPD · Beds · Nursing · Emergency · OT/Surgery · Billing · Payments · Refunds · Insurance/TPA · Inventory · Suppliers · Documents · Notifications · Reports · Audit Logs

## Build Phases

The backend is being built in the phases below. Each phase is committed independently once its migrations, models, services, controllers, routes, and policies exist and `php artisan migrate:fresh` runs clean. Status is kept up to date here as work lands.

- [x] **Phase 0 — Project Foundation**
  Laravel 11 install, MySQL config, Sanctum, git, base folder structure, API versioning scaffold (`routes/api_v1.php`), base `ApiResponse` helper/trait, global exception handler → consistent JSON error shape, base `Model` conventions.

- [x] **Phase 1 — Auth, Users, Roles & Permissions**
  `users`, `roles`, `permissions`, `role_user`, `permission_role` tables. Login/logout/me/forgot-password/reset-password. Sanctum tokens. `Role`/`Permission` models + `HasRoles`/`HasPermissions` traits. `CheckPermission` middleware. Base `Policy` pattern. Seeder for the 12 hospital roles. Audit log foundation (`audit_logs` table, `AuditLogService`, `Auditable` trait).

- [x] **Phase 2 — Patients & Patient 360°**
  `patients` table with auto-generated MRN. Full CRUD. `GET /patients/{id}/360` aggregating profile, history, appointments, visits, consultations, diagnoses, prescriptions, lab/radiology orders & reports, admissions, bills, payments, insurance, documents — eager-loaded, no N+1.

- [x] **Phase 3 — Appointments, OPD, Consultations**
  `appointments` (doctor/department/patient/date/time/type/status, check-in, reschedule, cancel), `opd_visits` (vitals, symptoms, diagnosis, notes, follow-up), `consultations` (chief complaint, examination, investigation orders) linking to diagnoses and prescriptions.

- [x] **Phase 4 — Diagnoses, Prescriptions & Pharmacy**
  `diagnoses`, `prescriptions` + `prescription_items`. Pharmacy: `medicines`, `medicine_categories`, `medicine_batches` (expiry/stock), `pharmacy_sales` + items, `pharmacy_purchases` + items, `pharmacy_returns`. Dispensing flow deducts batch stock inside a transaction; stock never goes negative unless a hospital-policy flag allows it; low-stock alerts.

- [x] **Phase 5 — Laboratory & Radiology**
  `lab_tests`, `lab_orders` + items, `lab_results` (reference ranges, values, approval workflow: order → collect → process → result → approve → report). `radiology_tests`, `radiology_orders`, `radiology_reports` (X-Ray/CT/MRI/Ultrasound/other).

- [x] **Phase 6 — IPD, Beds, Nursing, Emergency, OT**
  `wards`, `rooms`, `beds` (available/reserved/occupied/cleaning/maintenance), `admissions`, `bed_allocations` — a bed can never hold two concurrent active patients (DB-level + transactional guard). Nursing: `nursing_notes`, `patient_vitals`, `medication_administrations`, intake/output, shift handover. Emergency workflow (registration → triage → vitals → doctor → investigation → treatment → admit/discharge/refer). OT/Surgery scheduling and records.

- [x] **Phase 7 — Billing, Payments, Refunds, Insurance/TPA**
  `bills` + `bill_items`, `payments`, `refunds`, `discounts` across OPD/IPD/Pharmacy/Lab/Radiology/OT/Other. Unique invoice numbers, decimal money throughout. IPD billing accumulates room/nursing/doctor/medicine/lab/radiology/procedure/OT/consumable charges and produces a final bill at discharge. Multi-method payments (cash/card/UPI/bank/cheque/insurance/TPA), partial/advance/refund/outstanding. `insurance_companies`, `insurance_policies`, `insurance_claims`, `insurance_documents` with pre-auth → claim → approved/rejected → settlement.

- [x] **Phase 8 — Inventory & Suppliers**
  `inventory_items`, `inventory_transactions` (purchase/issue/return/adjustment/transfer), `suppliers`, `purchase_orders`.

- [x] **Phase 9 — Documents, Notifications, Reports, Audit Logs (hardening)**
  Secure, non-public document storage (prescriptions, lab/radiology reports, discharge summaries, insurance docs, consent forms) with authorized-only signed download URLs. Notification classes (appointment reminders, low stock, report-ready, payment receipts). Reporting endpoints (revenue, occupancy, pharmacy stock, lab turnaround, etc.). Audit log coverage review across every module in this list.

- [x] **Phase 10 — Testing**
  Not a separate pass — every phase above shipped with its own feature tests as it landed (119 tests total), including all three critical end-to-end flows named in this phase:
  - **OPD:** appointment → check-in → consultation → prescription → billing → payment (`OpdWorkflowTest`)
  - **Pharmacy:** prescription → dispensing → stock deduction → pharmacy bill (`PharmacyWorkflowTest`)
  - **IPD:** admission → bed allocation → charges → discharge → final bill → payment (`IpdFinalBillTest`)
  Plus bed allocation conflicts (`BedAllocationTest`), insurance claims (`InsuranceClaimTest`), refunds (`PaymentTest`), and RBAC boundary tests across every module.

- [x] **Phase 11 — Security Hardening & Review**
  Rate limiting (login throttle + global API throttle — see Security Notes). Full audit of every Policy (30) and every controller action (39 controllers) for real permission checks with no trivial `return true`. Mass-assignment review: no controller reads `$request->all()`, every write goes through a FormRequest's `validated()`/`safe()`. File-access audit on the Documents module. Secrets/env review. One High-severity gap found and fixed (see below); two Low-severity hardening items fixed; three Informational items reviewed and accepted as intentional design.

## Testing

```bash
php artisan test
```

## Security Notes

- No database credentials, API keys, or secrets are ever committed. `.env` is git-ignored; all `config/*.php` values are pulled via `env()`.
- All file downloads (documents, reports) are authorization-checked per request — nothing is served from a publicly-readable path. Uploads are restricted to a MIME allowlist (`pdf,jpg,jpeg,png,doc,docx`).
- Passwords are hashed via Laravel's default (bcrypt/argon2) hasher; never stored or logged in plaintext, and never included in audit log metadata.
- **Rate limiting**: `login`/`forgot-password`/`reset-password` are throttled to 5 attempts/minute keyed by email+IP together (so credential stuffing against many accounts from one IP is throttled per-target, not pooled). Every other route gets a general 120 req/min-per-user (else per-IP) throttle.
- **Full security audit (Phase 11)** covered authorization, mass assignment, SQL injection, file security, secret handling, data exposure, and IDOR across the whole codebase. Findings and resolutions:
  - **High — fixed:** a deactivated user could retain a still-valid token indefinitely if deactivated via the generic `PUT /users/{id}` endpoint instead of the dedicated `/deactivate` route (only the latter revoked tokens), and — independently — could in principle re-set their own `is_active` back to `true` through self-service profile updates. Fixed by revoking tokens in `UserService::update()` whenever `is_active` transitions to `false` regardless of which endpoint triggered it, and by stripping `is_active` from any self-service update that isn't made by a `users.update`-privileged actor (`UpdateUserRequest::prepareForValidation()`).
  - **Low — fixed:** document uploads had no MIME allowlist and `patient_id` wasn't type-checked before being used to build the storage path; added `mimes:pdf,jpg,jpeg,png,doc,docx` and an `integer` rule to `StoreDocumentRequest`.
  - **Informational — reviewed, no change:** `CheckPermission`/`CheckRole` middleware exist but authorization is enforced entirely at the controller/Policy layer rather than also at the route layer (no gap today, just no redundant backstop); insurance claim reject/settle reuse the `insurance.approve-claim` permission rather than dedicated ones (still correctly gated, just less granular for audit trails); several workflow actions (lab result approval, bed allocation, admission discharge) are permission-scoped rather than record-owner-scoped, matching normal hospital-staff-tool design where any qualified staff member can act on any patient.
