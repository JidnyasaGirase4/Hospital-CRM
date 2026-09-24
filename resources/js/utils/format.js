// Shared display formatters so every page renders money and dates the same way.

const money = new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export function formatMoney(value) {
    if (value === null || value === undefined || value === '') return '—';
    const n = Number(value);
    return Number.isFinite(n) ? money.format(n) : String(value);
}

export function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export function formatDateTime(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return `${formatDate(d)}, ${d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}`;
}

const DATE_ONLY = /^\d{4}-\d{2}-\d{2}$/;
const DATE_TIME = /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/;
const MONEY_KEY = /(amount|total|price|balance|outstanding|paid|cost|billed|collected|charge|fee|salary)$/;

export function isMoneyKey(key) {
    return MONEY_KEY.test(key);
}

// Best-effort formatting for table cells that have no explicit `format`.
export function autoFormat(key, value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'string') {
        if (DATE_TIME.test(value)) return formatDateTime(value);
        if (DATE_ONLY.test(value)) return formatDate(value);
        if (isMoneyKey(key) && /^-?\d+(\.\d+)?$/.test(value)) return formatMoney(value);
    } else if (typeof value === 'number' && isMoneyKey(key)) {
        return formatMoney(value);
    }
    return value;
}

// Staff names are stored with or without a title; never render "Dr. Dr. Name".
export function doctorName(name) {
    if (!name) return '—';
    return /^dr\.?\s/i.test(name) ? name : `Dr. ${name}`;
}
