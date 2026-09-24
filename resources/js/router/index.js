import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { NAV_MODULES } from './modules';

import AppShell from '../layouts/AppShell.vue';
import Login from '../pages/auth/Login.vue';
import ForgotPassword from '../pages/auth/ForgotPassword.vue';
import ResetPassword from '../pages/auth/ResetPassword.vue';
import Dashboard from '../pages/Dashboard.vue';
import MyProfile from '../pages/MyProfile.vue';
import ComingSoon from '../pages/ComingSoon.vue';

import PatientList from '../pages/patients/PatientList.vue';
import PatientForm from '../pages/patients/PatientForm.vue';
import PatientDetail from '../pages/patients/PatientDetail.vue';

import AppointmentList from '../pages/appointments/AppointmentList.vue';
import AppointmentForm from '../pages/appointments/AppointmentForm.vue';

import UserList from '../pages/users/UserList.vue';
import UserForm from '../pages/users/UserForm.vue';
import RoleList from '../pages/users/RoleList.vue';
import RoleForm from '../pages/users/RoleForm.vue';

import BillList from '../pages/billing/BillList.vue';
import BillForm from '../pages/billing/BillForm.vue';
import BillDetail from '../pages/billing/BillDetail.vue';
import PatientReport from '../pages/patients/PatientReport.vue';
import PaymentList from '../pages/billing/PaymentList.vue';
import PaymentForm from '../pages/billing/PaymentForm.vue';

import PolicyList from '../pages/insurance/PolicyList.vue';
import PolicyForm from '../pages/insurance/PolicyForm.vue';
import ClaimList from '../pages/insurance/ClaimList.vue';
import ClaimForm from '../pages/insurance/ClaimForm.vue';

import ClinicalHub from '../pages/clinical/ClinicalHub.vue';
import OpdVisitList from '../pages/clinical/OpdVisitList.vue';
import OpdVisitForm from '../pages/clinical/OpdVisitForm.vue';
import OpdVisitDetail from '../pages/clinical/OpdVisitDetail.vue';
import ConsultationList from '../pages/clinical/ConsultationList.vue';
import ConsultationForm from '../pages/clinical/ConsultationForm.vue';
import DiagnosisList from '../pages/clinical/DiagnosisList.vue';
import DiagnosisForm from '../pages/clinical/DiagnosisForm.vue';
import PrescriptionList from '../pages/clinical/PrescriptionList.vue';
import PrescriptionForm from '../pages/clinical/PrescriptionForm.vue';
import PrescriptionDetail from '../pages/clinical/PrescriptionDetail.vue';

import PharmacyHub from '../pages/pharmacy/PharmacyHub.vue';
import MedicineList from '../pages/pharmacy/MedicineList.vue';
import MedicineForm from '../pages/pharmacy/MedicineForm.vue';
import SupplierList from '../pages/pharmacy/SupplierList.vue';
import SupplierForm from '../pages/pharmacy/SupplierForm.vue';
import PurchaseList from '../pages/pharmacy/PurchaseList.vue';
import PurchaseForm from '../pages/pharmacy/PurchaseForm.vue';
import SaleList from '../pages/pharmacy/SaleList.vue';
import SaleForm from '../pages/pharmacy/SaleForm.vue';
import SaleDetail from '../pages/pharmacy/SaleDetail.vue';

import LabHub from '../pages/laboratory/LabHub.vue';
import LabOrderList from '../pages/laboratory/LabOrderList.vue';
import LabOrderForm from '../pages/laboratory/LabOrderForm.vue';
import LabOrderDetail from '../pages/laboratory/LabOrderDetail.vue';

import RadiologyHub from '../pages/radiology/RadiologyHub.vue';
import RadiologyOrderList from '../pages/radiology/RadiologyOrderList.vue';
import RadiologyOrderForm from '../pages/radiology/RadiologyOrderForm.vue';
import RadiologyOrderDetail from '../pages/radiology/RadiologyOrderDetail.vue';

import IpdHub from '../pages/ipd/IpdHub.vue';
import WardsBedsManager from '../pages/ipd/WardsBedsManager.vue';
import AdmissionList from '../pages/ipd/AdmissionList.vue';
import AdmissionForm from '../pages/ipd/AdmissionForm.vue';
import AdmissionDetail from '../pages/ipd/AdmissionDetail.vue';

import NursingHub from '../pages/nursing/NursingHub.vue';

import EmergencyList from '../pages/emergency/EmergencyList.vue';
import EmergencyForm from '../pages/emergency/EmergencyForm.vue';
import EmergencyDetail from '../pages/emergency/EmergencyDetail.vue';

import OtScheduleList from '../pages/ot/OtScheduleList.vue';
import OtScheduleForm from '../pages/ot/OtScheduleForm.vue';

import InventoryHub from '../pages/inventory/InventoryHub.vue';
import InventoryItemList from '../pages/inventory/InventoryItemList.vue';
import PurchaseOrderList from '../pages/inventory/PurchaseOrderList.vue';
import PurchaseOrderForm from '../pages/inventory/PurchaseOrderForm.vue';

import DocumentList from '../pages/documents/DocumentList.vue';
import NotificationList from '../pages/notifications/NotificationList.vue';
import ReportsPage from '../pages/reports/ReportsPage.vue';
import AuditLogList from '../pages/audit/AuditLogList.vue';

const readyChildren = [
    { path: '', name: 'dashboard', component: Dashboard, meta: { title: 'Dashboard' } },

    { path: 'patients', name: 'patients.index', component: PatientList, meta: { permission: 'patients.view', title: 'Patients' } },
    { path: 'patients/new', name: 'patients.create', component: PatientForm, meta: { permission: 'patients.create', title: 'New Patient' } },
    { path: 'patients/:id', name: 'patients.show', component: PatientDetail, props: true, meta: { permission: 'patients.view', title: 'Patient 360°' } },
    { path: 'patients/:id/edit', name: 'patients.edit', component: PatientForm, props: true, meta: { permission: 'patients.update', title: 'Edit Patient' } },

    { path: 'appointments', name: 'appointments.index', component: AppointmentList, meta: { permission: 'appointments.view', title: 'Appointments' } },
    { path: 'appointments/new', name: 'appointments.create', component: AppointmentForm, meta: { permission: 'appointments.create', title: 'New Appointment' } },

    { path: 'users', name: 'users.index', component: UserList, meta: { permission: 'users.view', title: 'Users' } },
    { path: 'users/new', name: 'users.create', component: UserForm, meta: { permission: 'users.create', title: 'New User' } },
    { path: 'users/:id/edit', name: 'users.edit', component: UserForm, props: true, meta: { permission: 'users.update', title: 'Edit User' } },

    { path: 'roles', name: 'roles.index', component: RoleList, meta: { permission: 'roles.view', title: 'Roles' } },
    { path: 'roles/new', name: 'roles.create', component: RoleForm, meta: { permission: 'roles.create', title: 'New Role' } },
    { path: 'roles/:id/edit', name: 'roles.edit', component: RoleForm, props: true, meta: { permission: 'roles.update', title: 'Edit Role' } },

    { path: 'billing', name: 'bills.index', component: BillList, meta: { permission: 'billing.view', title: 'Billing' } },
    { path: 'billing/new', name: 'bills.create', component: BillForm, meta: { permission: 'billing.create', title: 'New Bill' } },
    { path: 'billing/:id', name: 'bills.show', component: BillDetail, props: true, meta: { permission: 'billing.view', title: 'Bill Detail' } },
    { path: 'patients/:id/report', name: 'patients.report', component: PatientReport, props: true, meta: { permission: 'patients.view', title: 'Complete Patient Report' } },
    { path: 'billing/payments', name: 'payments.index', component: PaymentList, meta: { permission: 'payments.view', title: 'Payments' } },
    { path: 'billing/payments/new', name: 'payments.create', component: PaymentForm, meta: { permission: 'payments.create', title: 'Record Payment' } },

    { path: 'insurance', name: 'insurance-policies.index', component: PolicyList, meta: { permission: 'insurance.view', title: 'Insurance Policies' } },
    { path: 'insurance/new', name: 'insurance-policies.create', component: PolicyForm, meta: { permission: 'insurance.create', title: 'New Policy' } },
    { path: 'insurance/claims', name: 'insurance-claims.index', component: ClaimList, meta: { permission: 'insurance.view', title: 'Insurance Claims' } },
    { path: 'insurance/claims/new', name: 'insurance-claims.create', component: ClaimForm, meta: { permission: 'insurance.create', title: 'Submit Claim' } },

    { path: 'clinical', name: 'clinical.hub', component: ClinicalHub, meta: { permission: 'opd.view', title: 'OPD & Consultations' } },
    { path: 'clinical/opd-visits', name: 'opd-visits.index', component: OpdVisitList, meta: { permission: 'opd.view', title: 'OPD Visits' } },
    { path: 'clinical/opd-visits/new', name: 'opd-visits.create', component: OpdVisitForm, meta: { permission: 'opd.create', title: 'New OPD Visit' } },
    { path: 'clinical/opd-visits/:id', name: 'opd-visits.show', component: OpdVisitDetail, props: true, meta: { permission: 'opd.view', title: 'OPD Visit' } },
    { path: 'clinical/consultations', name: 'consultations.index', component: ConsultationList, meta: { permission: 'consultations.view', title: 'Consultations' } },
    { path: 'clinical/consultations/new', name: 'consultations.create', component: ConsultationForm, meta: { permission: 'consultations.create', title: 'New Consultation' } },
    { path: 'clinical/diagnoses', name: 'diagnoses.index', component: DiagnosisList, meta: { permission: 'diagnoses.view', title: 'Diagnoses' } },
    { path: 'clinical/diagnoses/new', name: 'diagnoses.create', component: DiagnosisForm, meta: { permission: 'diagnoses.create', title: 'New Diagnosis' } },
    { path: 'clinical/prescriptions', name: 'prescriptions.index', component: PrescriptionList, meta: { permission: 'prescriptions.view', title: 'Prescriptions' } },
    { path: 'clinical/prescriptions/new', name: 'prescriptions.create', component: PrescriptionForm, meta: { permission: 'prescriptions.create', title: 'New Prescription' } },
    { path: 'clinical/prescriptions/:id', name: 'prescriptions.show', component: PrescriptionDetail, props: true, meta: { permission: 'prescriptions.view', title: 'Prescription' } },

    { path: 'pharmacy', name: 'pharmacy.hub', component: PharmacyHub, meta: { permission: 'pharmacy.view', title: 'Pharmacy' } },
    { path: 'pharmacy/medicines', name: 'medicines.index', component: MedicineList, meta: { permission: 'pharmacy.view', title: 'Medicines' } },
    { path: 'pharmacy/medicines/new', name: 'medicines.create', component: MedicineForm, meta: { permission: 'pharmacy.create', title: 'New Medicine' } },
    { path: 'pharmacy/medicines/:id/edit', name: 'medicines.edit', component: MedicineForm, props: true, meta: { permission: 'pharmacy.update', title: 'Edit Medicine' } },
    { path: 'pharmacy/suppliers', name: 'suppliers.index', component: SupplierList, meta: { permission: 'suppliers.view', title: 'Suppliers' } },
    { path: 'pharmacy/suppliers/new', name: 'suppliers.create', component: SupplierForm, meta: { permission: 'suppliers.create', title: 'New Supplier' } },
    { path: 'pharmacy/suppliers/:id/edit', name: 'suppliers.edit', component: SupplierForm, props: true, meta: { permission: 'suppliers.create', title: 'Edit Supplier' } },
    { path: 'pharmacy/purchases', name: 'pharmacy-purchases.index', component: PurchaseList, meta: { permission: 'pharmacy.purchase', title: 'Pharmacy Purchases' } },
    { path: 'pharmacy/purchases/new', name: 'pharmacy-purchases.create', component: PurchaseForm, meta: { permission: 'pharmacy.purchase', title: 'New Purchase' } },
    { path: 'pharmacy/sales', name: 'pharmacy-sales.index', component: SaleList, meta: { permission: 'pharmacy.dispense', title: 'Pharmacy Sales' } },
    { path: 'pharmacy/sales/new', name: 'pharmacy-sales.create', component: SaleForm, meta: { permission: 'pharmacy.dispense', title: 'New Sale' } },
    { path: 'pharmacy/sales/:id', name: 'pharmacy-sales.show', component: SaleDetail, props: true, meta: { permission: 'pharmacy.view', title: 'Sale Detail' } },

    { path: 'laboratory', name: 'laboratory.hub', component: LabHub, meta: { permission: 'laboratory.view', title: 'Laboratory' } },
    { path: 'laboratory/orders', name: 'lab-orders.index', component: LabOrderList, meta: { permission: 'laboratory.view', title: 'Lab Orders' } },
    { path: 'laboratory/orders/new', name: 'lab-orders.create', component: LabOrderForm, meta: { permission: 'laboratory.create', title: 'New Lab Order' } },
    { path: 'laboratory/orders/:id', name: 'lab-orders.show', component: LabOrderDetail, props: true, meta: { permission: 'laboratory.view', title: 'Lab Order' } },

    { path: 'radiology', name: 'radiology.hub', component: RadiologyHub, meta: { permission: 'radiology.view', title: 'Radiology' } },
    { path: 'radiology/orders', name: 'radiology-orders.index', component: RadiologyOrderList, meta: { permission: 'radiology.view', title: 'Radiology Orders' } },
    { path: 'radiology/orders/new', name: 'radiology-orders.create', component: RadiologyOrderForm, meta: { permission: 'radiology.create', title: 'New Radiology Order' } },
    { path: 'radiology/orders/:id', name: 'radiology-orders.show', component: RadiologyOrderDetail, props: true, meta: { permission: 'radiology.view', title: 'Radiology Order' } },

    { path: 'ipd', name: 'ipd.hub', component: IpdHub, meta: { permission: 'ipd.view', title: 'IPD' } },
    { path: 'ipd/wards', name: 'wards.index', component: WardsBedsManager, meta: { permission: 'ipd.view', title: 'Wards, Rooms & Beds' } },
    { path: 'ipd/admissions', name: 'admissions.index', component: AdmissionList, meta: { permission: 'ipd.view', title: 'Admissions' } },
    { path: 'ipd/admissions/new', name: 'admissions.create', component: AdmissionForm, meta: { permission: 'ipd.admit', title: 'New Admission' } },
    { path: 'ipd/admissions/:id', name: 'admissions.show', component: AdmissionDetail, props: true, meta: { permission: 'ipd.view', title: 'Admission' } },

    { path: 'nursing', name: 'nursing.hub', component: NursingHub, meta: { permission: 'nursing.view', title: 'Nursing' } },

    { path: 'emergency', name: 'emergency-visits.index', component: EmergencyList, meta: { permission: 'emergency.view', title: 'Emergency' } },
    { path: 'emergency/new', name: 'emergency-visits.create', component: EmergencyForm, meta: { permission: 'emergency.create', title: 'Register Visit' } },
    { path: 'emergency/:id', name: 'emergency-visits.show', component: EmergencyDetail, props: true, meta: { permission: 'emergency.view', title: 'Emergency Visit' } },

    { path: 'ot', name: 'ot-schedules.index', component: OtScheduleList, meta: { permission: 'ot.view', title: 'OT / Surgery' } },
    { path: 'ot/new', name: 'ot-schedules.create', component: OtScheduleForm, meta: { permission: 'ot.create', title: 'Schedule Surgery' } },

    { path: 'inventory', name: 'inventory.hub', component: InventoryHub, meta: { permission: 'inventory.view', title: 'Inventory' } },
    { path: 'inventory/items', name: 'inventory-items.index', component: InventoryItemList, meta: { permission: 'inventory.view', title: 'Inventory Items' } },
    { path: 'inventory/purchase-orders', name: 'purchase-orders.index', component: PurchaseOrderList, meta: { permission: 'inventory.view', title: 'Purchase Orders' } },
    { path: 'inventory/purchase-orders/new', name: 'purchase-orders.create', component: PurchaseOrderForm, meta: { permission: 'inventory.create', title: 'New Purchase Order' } },

    { path: 'documents', name: 'documents.index', component: DocumentList, meta: { permission: 'documents.view', title: 'Documents' } },
    { path: 'notifications', name: 'notifications.index', component: NotificationList, meta: { title: 'Notifications' } },
    { path: 'reports', name: 'reports.index', component: ReportsPage, meta: { permission: 'reports.view', title: 'Reports' } },
    { path: 'audit-logs', name: 'audit-logs.index', component: AuditLogList, meta: { permission: 'audit-logs.view', title: 'Audit Logs' } },
];

const comingSoonChildren = NAV_MODULES.filter((m) => m.status === 'soon').map((m) => ({
    path: m.path.slice(1),
    name: `soon-${m.path.slice(1)}`,
    component: ComingSoon,
    props: { moduleLabel: m.label },
    meta: { title: m.label, permission: m.permission },
}));

const routes = [
    { path: '/login', name: 'login', component: Login, meta: { public: true } },
    { path: '/forgot-password', name: 'forgot-password', component: ForgotPassword, meta: { public: true } },
    { path: '/reset-password', name: 'reset-password', component: ResetPassword, meta: { public: true } },
    {
        path: '/',
        component: AppShell,
        children: [
            ...readyChildren,
            ...comingSoonChildren,
            { path: 'profile', name: 'profile', component: MyProfile, meta: { title: 'My Profile' } },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const auth = useAuthStore();

    if (to.meta.public) {
        if (to.name === 'login' && auth.isAuthenticated) return { name: 'dashboard' };
        return true;
    }

    if (!auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.permission && !auth.can(to.meta.permission)) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
