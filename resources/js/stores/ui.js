import { defineStore } from 'pinia';

let nextToastId = 0;

export const useUiStore = defineStore('ui', {
    state: () => ({
        toasts: [],
        confirmState: null,
        promptState: null,
        sidebarOpen: false,
    }),

    actions: {
        toast(message, type = 'info', timeout = 4000) {
            const id = ++nextToastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => this.dismissToast(id), timeout);
        },

        dismissToast(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },

        confirm({ title = 'Please confirm', message = '', confirmLabel = 'Confirm', danger = false } = {}) {
            return new Promise((resolve) => {
                this.confirmState = { title, message, confirmLabel, danger, resolve };
            });
        },

        resolveConfirm(result) {
            this.confirmState?.resolve(result);
            this.confirmState = null;
        },

        prompt({ title = 'Enter details', message = '', fields = [] } = {}) {
            return new Promise((resolve) => {
                this.promptState = { title, message, fields, resolve };
            });
        },

        resolvePrompt(result) {
            this.promptState?.resolve(result);
            this.promptState = null;
        },
    },
});
