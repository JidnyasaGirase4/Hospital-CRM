// Single source of truth for the sidebar nav. Each entry drives a route
// (see router/index.js), a permission gate, an icon, and which grouped
// section it renders under in the sidebar.
export const NAV_MODULES = [
    { label: 'Dashboard', path: '/', permission: null, status: 'ready', icon: 'home', group: null },

    { label: 'Patients', path: '/patients', permission: 'patients.view', status: 'ready', icon: 'user', group: 'Front Office' },
    { label: 'Appointments', path: '/appointments', permission: 'appointments.view', status: 'ready', icon: 'calendar', group: 'Front Office' },

    { label: 'OPD & Consultations', path: '/clinical', permission: 'opd.view', status: 'ready', icon: 'clipboard', group: 'Clinical' },
    { label: 'IPD & Beds', path: '/ipd', permission: 'ipd.view', status: 'ready', icon: 'bed', group: 'Clinical' },
    { label: 'Nursing', path: '/nursing', permission: 'nursing.view', status: 'ready', icon: 'heart', group: 'Clinical' },
    { label: 'Emergency', path: '/emergency', permission: 'emergency.view', status: 'ready', icon: 'alert', group: 'Clinical' },
    { label: 'OT / Surgery', path: '/ot', permission: 'ot.view', status: 'ready', icon: 'surgery', group: 'Clinical' },

    { label: 'Pharmacy', path: '/pharmacy', permission: 'pharmacy.view', status: 'ready', icon: 'archive', group: 'Pharmacy & Diagnostics' },
    { label: 'Laboratory', path: '/laboratory', permission: 'laboratory.view', status: 'ready', icon: 'beaker', group: 'Pharmacy & Diagnostics' },
    { label: 'Radiology', path: '/radiology', permission: 'radiology.view', status: 'ready', icon: 'scan', group: 'Pharmacy & Diagnostics' },

    { label: 'Billing', path: '/billing', permission: 'billing.view', status: 'ready', icon: 'banknote', group: 'Finance' },
    { label: 'Insurance', path: '/insurance', permission: 'insurance.view', status: 'ready', icon: 'shield', group: 'Finance' },

    { label: 'Inventory', path: '/inventory', permission: 'inventory.view', status: 'ready', icon: 'truck', group: 'Operations' },
    { label: 'Documents', path: '/documents', permission: 'documents.view', status: 'ready', icon: 'document', group: 'Operations' },

    { label: 'Reports', path: '/reports', permission: 'reports.view', status: 'ready', icon: 'chart', group: 'Insights' },
    { label: 'Notifications', path: '/notifications', permission: null, status: 'ready', icon: 'bell', group: 'Insights' },
    { label: 'Audit Logs', path: '/audit-logs', permission: 'audit-logs.view', status: 'ready', icon: 'shield-check', group: 'Insights' },

    { label: 'Users & Roles', path: '/users', permission: 'users.view', status: 'ready', icon: 'users', group: 'Administration' },
];
