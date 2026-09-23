import { defineStore } from 'pinia';
import apiClient from '../api/client';

function readStoredUser() {
    try {
        return JSON.parse(localStorage.getItem('auth_user') || 'null');
    } catch {
        return null;
    }
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('auth_token') || null,
        user: readStoredUser(),
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        permissions: (state) => state.user?.permissions || [],
    },

    actions: {
        can(permission) {
            if (!permission) return true;
            return this.permissions.includes(permission);
        },

        async login(email, password) {
            const { data } = await apiClient.post('/auth/login', { email, password });
            this.setSession(data.data.token, data.data.user);
        },

        setSession(token, user) {
            this.token = token;
            this.user = user;
            localStorage.setItem('auth_token', token);
            localStorage.setItem('auth_user', JSON.stringify(user));
        },

        async fetchMe() {
            const { data } = await apiClient.get('/auth/me');
            this.user = data.data;
            localStorage.setItem('auth_user', JSON.stringify(this.user));
        },

        async logout() {
            try {
                await apiClient.post('/auth/logout');
            } catch {
                // token may already be invalid/expired - clear local state regardless
            }
            this.clearSession();
        },

        clearSession() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
        },
    },
});
