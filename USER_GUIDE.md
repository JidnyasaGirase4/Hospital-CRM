# Hospital CRM — User Guide

## 1. Log in

Go to `/login` and sign in.

- Default admin (from `DatabaseSeeder`): `admin@gmail.com` / `password` (change this in production).
- If demo data has been seeded (`php artisan db:seed --class=DemoDataSeeder`), 7 additional sample staff accounts exist (doctor, nurse, receptionist, pharmacist, lab technician, accountant) — see `database/seeders/DemoDataSeeder.php` for their emails.

## 2. Navigation

The left sidebar is grouped into:

- **Front Office**: Patients, Appointments
- **Clinical**: OPD & Consultations, IPD & Beds, Nursing, Emergency, OT/Surgery
- **Pharmacy & Diagnostics**: Pharmacy, Laboratory, Radiology
- **Finance**: Billing, Insurance
- **Operations**: Inventory, Documents
- **Insights**: Reports, Notifications, Audit Logs
- **Administration**: Users & Roles

Each menu item only appears if your logged-in role has the matching permission (e.g. `patients.view`) — this is why different staff logins show different sidebars.

## 3. The Add / Edit / Delete pattern (same across every module)

- **List page** (e.g. Patients, Bills, Medicines): has a search box, a **"+ New …"** button top-right (only visible if you have the `*.create` permission), and a table of records.
- **Add**: click **"+ New X"** → fill the form → Save. Takes you to the record's detail page.
- **View details**: click the record's name/code link in the table.
- **Edit**: click **"Edit"** in the row or on the detail page (needs `*.update` permission) → change fields → Save.
- **Delete**: most clinical/financial records (patients, bills, admissions, prescriptions, etc.) are **not hard-deletable** — that's intentional, for audit/compliance reasons. Instead they use an **Active/Inactive toggle** (e.g. deactivate a user or patient record) or a **status field** (e.g. cancel an appointment, void a bill). Only a few non-clinical records support a real **Delete** button with a confirmation dialog — currently just **Documents** (`documents.delete` permission).

## 4. Module-by-module quick reference

| Module | Add | Edit | "Remove" |
|---|---|---|---|
| Patients | New Patient → registration form | Edit demographics/contact | Deactivate (Active toggle) |
| Appointments | New Appointment, pick patient/doctor/slot | Reschedule/edit | Cancel (status change) |
| OPD & Consultations | New OPD Visit → add Consultation, Diagnosis, Prescription | Edit visit/consultation notes | N/A (clinical record) |
| IPD & Beds | New Admission; manage Wards/Beds in "Wards & Beds Manager" | Edit admission, transfer bed | Discharge (status change) |
| Nursing | Nursing hub actions tied to admitted patients | — | — |
| Emergency | New Emergency case | Edit triage/status | Close case (status) |
| OT / Surgery | New OT Schedule (book theatre/surgeon/slot) | Edit schedule | Cancel |
| Pharmacy | New Medicine, Supplier, Purchase, Sale | Edit stock/price/details | Deactivate medicine/supplier |
| Laboratory | New Lab Order | Edit/enter results | Cancel order |
| Radiology | New Radiology Order | Edit/enter results | Cancel order |
| Billing | New Bill, record Payment | Edit line items | Void bill (status) |
| Insurance | New Policy, New Claim | Edit claim status | — |
| Inventory | New Inventory Item, Purchase Order | Edit stock/reorder levels | Deactivate item |
| Documents | Upload Document | — | **Delete** (real delete, with confirm dialog) |
| Users & Roles | New User, New Role (assign permissions) | Edit user's role/department, toggle Active | Deactivate user (no hard delete) |
| Reports / Audit Logs / Notifications | Read-only — no add/edit/delete | — | — |

## 5. Permissions

If you don't see a "+ New" or "Edit" button, your role lacks that permission. An admin can grant it via **Users & Roles → Roles → edit permissions**, or assign a different role to a user in **Users & Roles → Users → Edit**.
