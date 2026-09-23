# Hospital CRM / Hospital Management System

## Current Implementation Status

This README documents the full project vision and phased plan. Actual progress against it, as of now:

- **Backend (Laravel API):** Phases 0–11 of the backend are complete — auth, RBAC, patients & Patient 360°, appointments/OPD/consultations, pharmacy, laboratory, radiology, IPD/beds/nursing/emergency/OT, billing/payments/insurance, inventory/suppliers, documents/notifications/reports/audit logs, full test suite (121 tests), and a security hardening pass. See `routes/v1/*.php` and `app/Http/Controllers/Api/V1/` for what's live.
- **Frontend:** the plan below specifies a separate Vanilla JS/HTML/CSS frontend project. What actually exists today is an **interim Vue 3 SPA embedded in this Laravel app** (`resources/js/`), served at `/` via `php artisan serve` — no separate frontend project or CORS setup needed yet. It currently has working screens for **Dashboard, Patients (incl. Patient 360°), Appointments, and Users & Roles**; the remaining modules listed below show as "Soon" placeholders in the sidebar pending their own screens. Whether to continue in Vue or rebuild per the Vanilla JS plan below is an open decision.
- **Local login:** seeded Super Admin — `admin@hospital-crm.test` / `password` (see `database/seeders/DatabaseSeeder.php`).

Everything from here down is the original project specification/roadmap.

------------------------------------------------------------------------

## 1. Project Overview

This project is a full-featured Hospital CRM / Hospital Management
System built with:

-   **Frontend:** HTML5, CSS3, Vanilla JavaScript
-   **Backend:** Laravel (PHP)
-   **Database:** MySQL
-   **Architecture:** REST API based frontend/backend separation

### Main Goal

Build a centralized hospital system where one Patient ID connects the
patient's complete lifecycle:

**Registration → Appointment → OPD → Doctor Consultation → Prescription
→ Pharmacy → Lab → Radiology → IPD → Billing → Payment → Insurance →
Discharge → Follow-up**

The most important concept is **Patient 360°**, where authorized
hospital staff can see a patient's complete history, medical records,
bills, medicines, reports, payments and documents from one place.

------------------------------------------------------------------------

# 2. Development Rules

## General Rules

1.  Build the project phase by phase.
2.  Do not skip database relationships or validation.
3.  Do not create duplicate patient records unnecessarily.
4.  Every patient must have a unique Patient ID / MRN.
5.  Every bill must have a unique invoice number.
6.  Use role-based access control.
7.  Validate all frontend and backend inputs.
8.  Backend validation is mandatory even if frontend validation exists.
9.  Use Laravel migrations for database changes.
10. Use Laravel seeders/factories for development/demo data.
11. Use REST APIs between frontend and Laravel.
12. Never expose sensitive information to unauthorized users.
13. Keep reusable JavaScript modules instead of one huge JS file.
14. Keep CSS modular and responsive.
15. Use transactions for financial and critical operations.
16. Use audit logs for important patient, billing and administrative
    actions.
17. Do not hard-delete important medical or financial records. Prefer
    status/soft deletion where appropriate.
18. Keep the code production-ready and maintainable.

------------------------------------------------------------------------

# 3. User Roles

The system should support:

-   Super Admin
-   Hospital Admin
-   Doctor
-   Nurse
-   Receptionist
-   Billing Staff
-   Pharmacist
-   Lab Technician
-   Radiology Staff
-   OT Staff
-   Accountant
-   Inventory/Store Manager
-   HR/Staff Manager

Permissions must be role-based.

Example:

### Receptionist

Can: - Register patients - Manage appointments - Check-in patients -
Create permitted bills

Cannot: - Edit doctor clinical notes - Access payroll - Change system
settings

### Doctor

Can: - View assigned patients - View medical history - Create
consultation notes - Create diagnosis - Create prescriptions - Order
lab/radiology tests - Create follow-up appointments

### Pharmacist

Can: - View prescriptions - Dispense medicines - Manage pharmacy sales -
Manage stock - Manage batches and expiry

------------------------------------------------------------------------

# 4. Main Modules

``` text
Dashboard
Patients
Patient 360°
Appointments
OPD
IPD
Emergency
Doctors
Nursing
EMR / EHR
Pharmacy
Laboratory
Radiology
OT
Billing
Payments
Insurance / TPA
Inventory
Suppliers
Documents
Notifications
Reports & Analytics
Users & Roles
Settings
Audit Logs
```

------------------------------------------------------------------------

# 5. Development Phases

## PHASE 0 --- Project Planning & Architecture

### Tasks

-   Finalize requirements
-   Finalize user roles
-   Finalize module list
-   Define frontend/backend architecture
-   Define API naming conventions
-   Define database naming conventions
-   Define authentication strategy
-   Define permission strategy
-   Define error response format
-   Define audit strategy
-   Define folder structure

### Deliverables

-   Complete README
-   Database ERD
-   API documentation structure
-   Frontend folder structure
-   Laravel folder structure
-   Role/permission matrix

------------------------------------------------------------------------

# PHASE 1 --- Frontend Foundation

## Objective

Create the complete reusable frontend foundation before implementing
business modules.

### Frontend Tasks

-   Login page
-   Forgot password page
-   Dashboard shell
-   Sidebar
-   Header
-   Breadcrumbs
-   Page layouts
-   Responsive layout
-   Modal component
-   Alert/toast component
-   Confirmation dialog
-   Data table component
-   Pagination component
-   Search component
-   Filter component
-   Form components
-   Loading states
-   Empty states
-   Error states
-   Profile dropdown
-   Role-based menu visibility

### Frontend Folder Structure

``` text
frontend/
│
├── index.html
├── login.html
│
├── dashboard/
│   └── index.html
│
├── patients/
├── appointments/
├── opd/
├── ipd/
├── emergency/
├── doctors/
├── nursing/
├── pharmacy/
├── laboratory/
├── radiology/
├── billing/
├── payments/
├── insurance/
├── inventory/
├── reports/
├── users/
├── settings/
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── icons/
│
└── components/
```

### Important

At this stage use mock/static JSON data only.

Do not connect to Laravel yet.

------------------------------------------------------------------------

# PHASE 2 --- Backend Foundation

## Objective

Create the Laravel backend, authentication, database structure and API
foundation.

### Tasks

-   Create Laravel project
-   Configure MySQL
-   Configure `.env`
-   Create migrations
-   Create models
-   Create relationships
-   Create API routes
-   Create controllers
-   Create form requests
-   Create API resources
-   Authentication
-   Role management
-   Permission management
-   Exception handling
-   API response format
-   Logging
-   Audit logging
-   CORS configuration
-   Database seeders
-   Factories

### Suggested Core Tables

``` text
users
roles
permissions
role_user
permission_role

patients
patient_contacts
patient_documents
patient_allergies
patient_medical_histories

departments
doctors
doctor_schedules

appointments
appointment_statuses

opd_visits
consultations
diagnoses
prescriptions
prescription_items

medicines
medicine_categories
medicine_batches
medicine_units

pharmacy_sales
pharmacy_sale_items
pharmacy_purchases
pharmacy_purchase_items
pharmacy_returns

lab_tests
lab_orders
lab_order_items
lab_results

radiology_tests
radiology_orders
radiology_reports

wards
rooms
beds
admissions
bed_allocations
nursing_notes

ot_schedules
surgeries
ot_consumables

bills
bill_items
payments
refunds
discounts

insurance_companies
insurance_policies
insurance_claims
insurance_documents

suppliers
inventory_transactions

notifications
audit_logs
```

------------------------------------------------------------------------

# PHASE 3 --- Authentication & Authorization

## Frontend

Create:

-   Login
-   Logout
-   Session handling
-   Token handling
-   Unauthorized page
-   Forbidden page
-   Permission-based buttons
-   Permission-based menu

## Backend

Implement:

-   Authentication API
-   Login
-   Logout
-   Password reset
-   User management
-   Role management
-   Permission management
-   API authorization middleware

------------------------------------------------------------------------

# PHASE 4 --- Patient Management

## Frontend Screens

### Patient List

Columns:

-   Patient ID
-   Name
-   Age
-   Gender
-   Mobile
-   Blood Group
-   Last Visit
-   Status
-   Actions

Features:

-   Search
-   Filter
-   Pagination
-   Add patient
-   Edit patient
-   View patient
-   Export

### Patient Registration

Fields:

-   Patient ID / MRN
-   Full name
-   DOB
-   Gender
-   Mobile
-   Email
-   Address
-   Emergency contact
-   Blood group
-   Allergies
-   Insurance
-   ID information where appropriate

### Patient 360°

Tabs:

``` text
Overview
Medical History
Visits
Consultations
Prescriptions
Medicines
Laboratory
Radiology
Admissions
Bills
Payments
Insurance
Documents
Appointments
```

------------------------------------------------------------------------

# PHASE 5 --- Appointment Management

### Features

-   Appointment creation
-   Doctor selection
-   Department selection
-   Date/time slots
-   New/follow-up
-   Appointment status
-   Reschedule
-   Cancel
-   Check-in
-   Queue/token
-   Doctor availability
-   Follow-up appointment

------------------------------------------------------------------------

# PHASE 6 --- OPD & Doctor Consultation

## Doctor Consultation

Fields:

-   Chief complaint
-   Symptoms
-   Vitals
-   Examination
-   Medical history
-   Diagnosis
-   Clinical notes
-   Investigation orders
-   Prescription
-   Follow-up
-   Referral

### Prescription

Prescription item:

-   Medicine
-   Dosage
-   Frequency
-   Route
-   Duration
-   Timing
-   Instructions

Flow:

``` text
Appointment
    ↓
Patient Check-in
    ↓
Doctor Consultation
    ↓
Diagnosis
    ↓
Investigation
    ↓
Prescription
    ↓
Billing
    ↓
Pharmacy
    ↓
Follow-up
```

------------------------------------------------------------------------

# PHASE 7 --- Pharmacy

### Medicine Master

-   Medicine name
-   Generic name
-   Brand
-   Manufacturer
-   Category
-   Unit
-   HSN
-   Tax
-   Selling price
-   Reorder level

### Batch

-   Batch number
-   Expiry date
-   Purchase price
-   Selling price
-   Quantity

### Features

-   Prescription dispensing
-   Pharmacy billing
-   Purchases
-   Purchase returns
-   Sales returns
-   Stock adjustment
-   Low-stock alert
-   Expiry alert
-   Batch tracking
-   Supplier management

Flow:

``` text
Prescription
    ↓
Pharmacy
    ↓
Batch Selection
    ↓
Stock Deduction
    ↓
Pharmacy Bill
    ↓
Payment
```

------------------------------------------------------------------------

# PHASE 8 --- Billing & Payments

## Billing Types

-   OPD
-   IPD
-   Pharmacy
-   Laboratory
-   Radiology
-   OT
-   Other services

### Bill Structure

``` text
Bill
├── Patient
├── Visit/Admission
├── Bill Items
├── Discount
├── Tax
├── Total
├── Paid
├── Refund
└── Outstanding
```

### Payment Methods

-   Cash
-   Card
-   UPI
-   Bank Transfer
-   Cheque
-   Insurance
-   TPA

### Features

-   Create bill
-   Draft bill
-   Finalize bill
-   Print invoice
-   Download invoice
-   Partial payment
-   Advance payment
-   Refund
-   Discount
-   Outstanding balance
-   Payment history

Use database transactions for bill/payment operations.

------------------------------------------------------------------------

# PHASE 9 --- IPD / Admission

### Admission

-   Admission number
-   Patient
-   Doctor
-   Department
-   Ward
-   Room
-   Bed
-   Admission date
-   Expected discharge
-   Insurance

### Bed Management

``` text
Ward
 ├── Room
 │    ├── Bed
 │    ├── Bed
 │    └── Bed
```

Statuses:

-   Available
-   Reserved
-   Occupied
-   Cleaning
-   Maintenance

### IPD Charges

Automatically accumulate:

-   Room
-   Nursing
-   Doctor
-   Medicine
-   Lab
-   Radiology
-   Procedure
-   OT
-   Consumables
-   Other charges

------------------------------------------------------------------------

# PHASE 10 --- Nursing

Features:

-   Patient assignment
-   Vitals
-   Nursing notes
-   Medication administration
-   Intake/output
-   Care plan
-   Doctor instructions
-   Shift handover

------------------------------------------------------------------------

# PHASE 11 --- Laboratory

Flow:

``` text
Doctor Order
    ↓
Lab Order
    ↓
Sample Collection
    ↓
Processing
    ↓
Result Entry
    ↓
Doctor Approval
    ↓
Report
```

Features:

-   Test master
-   Test pricing
-   Sample tracking
-   Barcode support
-   Result entry
-   Reference ranges
-   Critical values
-   Report generation
-   PDF report
-   Doctor approval

------------------------------------------------------------------------

# PHASE 12 --- Radiology

Features:

-   Test master
-   X-Ray
-   CT
-   MRI
-   Ultrasound
-   Order management
-   Scheduling
-   Report entry
-   Report approval
-   Report download

Optional future integration:

-   PACS
-   DICOM
-   RIS

------------------------------------------------------------------------

# PHASE 13 --- Emergency

Workflow:

``` text
Emergency Registration
        ↓
Triage
        ↓
Vitals
        ↓
Doctor
        ↓
Investigation
        ↓
Treatment
        ↓
Admission / Discharge / Referral
```

Priority:

-   Critical
-   Urgent
-   Semi-urgent
-   Normal

------------------------------------------------------------------------

# PHASE 14 --- Insurance / TPA

Features:

-   Insurance company
-   Policy
-   Member ID
-   Coverage
-   Pre-authorization
-   Claim creation
-   Claim documents
-   Claim amount
-   Approved amount
-   Rejected amount
-   Claim status
-   Settlement

------------------------------------------------------------------------

# PHASE 15 --- OT / Surgery

Features:

-   OT schedule
-   Surgery booking
-   Surgeon
-   Anaesthetist
-   OT staff
-   Procedure
-   Pre-op checklist
-   Consent
-   Post-op notes
-   Consumables
-   OT charges
-   Surgery report

------------------------------------------------------------------------

# PHASE 16 --- Inventory & Suppliers

### Inventory

-   Item master
-   Categories
-   Units
-   Stock
-   Stock adjustment
-   Stock transfer
-   Purchase
-   Purchase return
-   Issue
-   Consumption

### Supplier

-   Supplier profile
-   Contact
-   Purchase history
-   Outstanding
-   Payment history

------------------------------------------------------------------------

# PHASE 17 --- Documents

Patient documents:

-   ID documents
-   Insurance documents
-   Prescriptions
-   Lab reports
-   Radiology reports
-   Discharge summaries
-   Consent forms
-   Medical certificates

Features:

-   Upload
-   Preview
-   Download
-   Categorize
-   Access control
-   Audit trail

------------------------------------------------------------------------

# PHASE 18 --- Reports & Analytics

## Patient Reports

-   Daily patient count
-   Monthly patient count
-   New patients
-   Returning patients
-   Department-wise
-   Doctor-wise

## Financial Reports

-   Daily collection
-   Monthly revenue
-   OPD revenue
-   IPD revenue
-   Pharmacy revenue
-   Lab revenue
-   Radiology revenue
-   Outstanding
-   Refund
-   Discount
-   Payment method

## Pharmacy Reports

-   Stock
-   Low stock
-   Expired
-   Near expiry
-   Fast moving
-   Slow moving
-   Purchase vs sales

## Hospital Reports

-   Bed occupancy
-   Admissions
-   Discharges
-   Average length of stay
-   Department performance
-   Doctor performance

------------------------------------------------------------------------

# PHASE 19 --- Notifications

Notification events:

-   Appointment confirmation
-   Appointment reminder
-   Appointment cancellation
-   Lab report ready
-   Bill generated
-   Payment received
-   Outstanding reminder
-   Follow-up reminder
-   Low stock
-   Medicine expiry

Channels can include:

-   In-app
-   Email
-   SMS
-   WhatsApp

Implement external messaging integrations only after the core system is
stable.

------------------------------------------------------------------------

# PHASE 20 --- Security, Audit & Compliance

Implement:

-   Role-based access
-   Permission checks
-   Secure authentication
-   Password hashing
-   API authorization
-   Audit logs
-   Login history
-   Failed login tracking
-   Secure file access
-   Database backups
-   Error logging
-   Soft deletes where appropriate
-   Sensitive-data protection

For an India-focused deployment, review applicable
privacy/data-protection requirements and healthcare interoperability
requirements before production use.

------------------------------------------------------------------------

# PHASE 21 --- Frontend + Backend Integration

Only after the frontend screens and backend APIs are stable.

### Integration Order

``` text
Authentication
      ↓
Patients
      ↓
Appointments
      ↓
OPD
      ↓
Consultation
      ↓
Prescription
      ↓
Pharmacy
      ↓
Billing
      ↓
Payments
      ↓
IPD
      ↓
Lab
      ↓
Radiology
      ↓
Insurance
      ↓
Reports
```

### API Rules

Use consistent responses:

``` json
{
  "success": true,
  "message": "Patient created successfully",
  "data": {},
  "errors": null
}
```

Validation error:

``` json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "errors": {}
}
```

------------------------------------------------------------------------

# 6. Patient 360° Data Relationship

The central relationship should be:

``` text
                    PATIENT
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   Appointments      Visits       Admissions
        │              │              │
        │         Consultation         │
        │              │              │
        │        ┌─────┴─────┐         │
        │   Diagnosis   Prescription   │
        │                  │            │
        │              Pharmacy         │
        │                               │
        ├──────── Laboratory ────────────┤
        ├──────── Radiology ─────────────┤
        ├──────── Documents ─────────────┤
        ├──────── Insurance ─────────────┤
        └──────── Billing/Payments ──────┘
```

------------------------------------------------------------------------

# 7. Suggested Laravel Structure

``` text
app/
├── Http/
│   ├── Controllers/Api/
│   ├── Requests/
│   └── Resources/
│
├── Models/
│
├── Services/
│   ├── PatientService.php
│   ├── BillingService.php
│   ├── PharmacyService.php
│   ├── AppointmentService.php
│   └── AdmissionService.php
│
├── Policies/
├── Notifications/
└── Exceptions/

database/
├── migrations/
├── seeders/
└── factories/

routes/
└── api.php
```

Use Services for complex business logic instead of putting everything
inside controllers.

------------------------------------------------------------------------

# 8. Suggested API Structure

``` text
/api/v1/auth
/api/v1/users
/api/v1/roles
/api/v1/patients
/api/v1/appointments
/api/v1/opd
/api/v1/consultations
/api/v1/prescriptions
/api/v1/pharmacy
/api/v1/medicines
/api/v1/laboratory
/api/v1/radiology
/api/v1/ipd
/api/v1/beds
/api/v1/nursing
/api/v1/billing
/api/v1/payments
/api/v1/insurance
/api/v1/inventory
/api/v1/reports
/api/v1/documents
```

Use API versioning from the beginning.

------------------------------------------------------------------------

# 9. Database Principles

Important relationships:

``` text
patients
  ↓
appointments
  ↓
opd_visits
  ↓
consultations
  ↓
prescriptions
  ↓
prescription_items
  ↓
medicines

patients
  ↓
admissions
  ↓
bed_allocations

patients
  ↓
lab_orders
  ↓
lab_results

patients
  ↓
radiology_orders
  ↓
radiology_reports

patients
  ↓
bills
  ↓
bill_items
  ↓
payments
```

Use:

-   Primary keys
-   Foreign keys
-   Indexes
-   Unique constraints
-   Timestamps
-   Soft deletes where appropriate
-   Proper decimal types for money
-   Transactions for critical operations

------------------------------------------------------------------------

# 10. Testing Plan

## Frontend Testing

Test:

-   Forms
-   Validation
-   Search
-   Filters
-   Tables
-   Modals
-   Responsive layout
-   Permission-based UI

## Backend Testing

Test:

-   Authentication
-   Authorization
-   API validation
-   CRUD
-   Relationships
-   Billing calculations
-   Payment calculations
-   Pharmacy stock deduction
-   Admission/bed allocation
-   Lab workflow
-   Reports

## Critical Scenarios

### Scenario 1

``` text
Register Patient
→ Appointment
→ OPD
→ Consultation
→ Prescription
→ Pharmacy
→ Bill
→ Payment
```

### Scenario 2

``` text
Patient
→ Admission
→ Bed Allocation
→ Daily Charges
→ Medicines
→ Lab
→ Treatment
→ Discharge
→ Final Bill
→ Payment
```

------------------------------------------------------------------------

# 11. Final Production Checklist

Before deployment:

-   [ ] Authentication tested
-   [ ] Roles tested
-   [ ] Permissions tested
-   [ ] Patient registration tested
-   [ ] Patient 360 tested
-   [ ] Appointment tested
-   [ ] OPD tested
-   [ ] Prescription tested
-   [ ] Pharmacy stock tested
-   [ ] Billing calculations tested
-   [ ] Payment tested
-   [ ] Refund tested
-   [ ] IPD tested
-   [ ] Bed allocation tested
-   [ ] Lab tested
-   [ ] Radiology tested
-   [ ] Insurance tested
-   [ ] Documents tested
-   [ ] Reports tested
-   [ ] Audit logs tested
-   [ ] Database backup tested
-   [ ] API security reviewed
-   [ ] File upload security reviewed
-   [ ] Production `.env` configured
-   [ ] Error handling reviewed
-   [ ] Performance tested

------------------------------------------------------------------------

# 12. Important Development Approach

Do not try to build all modules simultaneously.

Use this order:

**Plan → Frontend Foundation → Frontend Modules → Backend Foundation →
Backend Modules → API Integration → Testing → Security → Production**

For each module:

``` text
1. Requirement
2. Database design
3. Frontend UI
4. Frontend validation
5. Laravel migration
6. Model + relationships
7. API
8. Backend validation
9. Business logic
10. Frontend API integration
11. Testing
12. Fix bugs
13. Move to next module
```

------------------------------------------------------------------------

# 13. Definition of Done

A module is complete only when:

-   UI is complete
-   Responsive design works
-   Form validation works
-   Database is implemented
-   Laravel API works
-   Authorization works
-   Error handling works
-   Loading states work
-   Empty states work
-   CRUD is tested
-   Relationships are tested
-   Audit requirements are handled
-   Frontend is connected to API
-   End-to-end workflow works

Do not mark a module complete just because its UI is finished.

------------------------------------------------------------------------

# 14. Future Enhancements

After the core HMS is stable:

-   Patient mobile app
-   Doctor mobile app
-   Online appointment booking
-   Online payment gateway
-   WhatsApp integration
-   SMS gateway
-   Email
-   ABHA/ABDM integrations where applicable
-   E-prescription
-   Telemedicine
-   PACS/DICOM
-   Advanced dashboards
-   Multi-branch hospital management
-   Multi-language support
-   Advanced financial accounting
-   AI-assisted administrative features

------------------------------------------------------------------------

# 15. Final Product Vision

The final system should behave as one connected hospital platform:

``` text
                         HOSPITAL CRM
                              │
       ┌──────────────────────┼──────────────────────┐
       │                      │                      │
   FRONT OFFICE           CLINICAL               OPERATIONS
       │                      │                      │
 Registration              Doctors                Pharmacy
 Appointment               EMR                    Inventory
 Billing                   Nursing                Lab
 Payments                  Prescription            Radiology
       │                      │                      │
       └──────────────────────┼──────────────────────┘
                              │
                         PATIENT 360°
                              │
                ┌─────────────┼─────────────┐
                │             │             │
             Medical       Financial      Documents
             History        History        & Reports
```

The goal is not just to create a billing application. The goal is to
create a **centralized hospital platform where every authorized
department works from the same patient record and connected workflows**.
