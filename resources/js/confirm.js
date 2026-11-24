import { reactive } from 'vue';

/** Global confirmation dialog (rendered once in AdminLayout). */
export const confirmState = reactive({ open: false, title: '', text: '', label: 'Confirmer', danger: true, resolve: null });

export function confirmDialog({ title, text = '', label = 'Confirmer', danger = true }) {
    return new Promise((resolve) => Object.assign(confirmState, { open: true, title, text, label, danger, resolve }));
}

export function closeConfirm(result) {
    confirmState.resolve?.(result);
    Object.assign(confirmState, { open: false, resolve: null });
}
