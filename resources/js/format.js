const dateFmt = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Africa/Conakry' });
const longFmt = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Africa/Conakry' });
const dateTimeFmt = new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Africa/Conakry' });

export const formatDate = (iso) => (iso ? dateFmt.format(new Date(iso)) : '');
export const formatLongDate = (iso) => (iso ? longFmt.format(new Date(iso)) : '');
export const formatDateTime = (iso) => (iso ? dateTimeFmt.format(new Date(iso)) : '');
export const money = (value, currency = '') => {
    if (value === null || value === undefined || value === '') return '';
    const text = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: Number(value) % 1 ? 2 : 0 }).format(Number(value));
    return currency ? `${text} ${currency}` : text;
};
export const initials = (value) => String(value || '').replace('@', ' ').split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?';
export const timeAgo = (iso) => {
    if (!iso) return '';
    const seconds = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
    const rtf = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });
    for (const [unit, size] of [['year', 31536000], ['month', 2592000], ['day', 86400], ['hour', 3600], ['minute', 60]]) {
        if (Math.abs(seconds) >= size) return rtf.format(-Math.round(seconds / size), unit);
    }
    return "à l'instant";
};
