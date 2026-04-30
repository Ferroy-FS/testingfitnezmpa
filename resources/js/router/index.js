/**
 * Vue Router — FitNez
 *
 * Halaman Publik dan Private (poin e):
 * - Publik: Landing, Login, Register (tanpa auth)
 * - Private: Dashboard (perlu auth + OTP)
 */

import { createRouter, createWebHistory } from 'vue-router';
import Http from '../utils/http.js';

// Lazy-loaded pages
const LandingPage       = () => import('../pages/LandingPage.vue');
const LoginPage         = () => import('../pages/LoginPage.vue');
const RegisterPage      = () => import('../pages/RegisterPage.vue');
const OtpPage           = () => import('../pages/OtpPage.vue');
const TrainerRegister   = () => import('../pages/TrainerRegisterPage.vue');
const DashboardPage     = () => import('../pages/DashboardPage.vue');

const routes = [
    // ── HALAMAN PUBLIK (poin e.1) ──
    {
        path: '/',
        name: 'landing',
        component: LandingPage,
        meta: { public: true },
    },
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: { public: true, guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: RegisterPage,
        meta: { public: true, guest: true },
    },
    {
        path: '/verify-otp',
        name: 'verify-otp',
        component: OtpPage,
        meta: { public: true },
    },

    // ── HALAMAN PRIVATE (poin e.3, e.4) ──
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/trainer-register',
        name: 'trainer-register',
        component: TrainerRegister,
        meta: { requiresAuth: true },
    },

    // Fallback
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// ── Navigation Guard ──
router.beforeEach((to, from, next) => {
    const token = Http.getToken();
    const otpVerified = Http.isOtpVerified();

    // Protected route — perlu login + OTP
    if (to.meta.requiresAuth) {
        if (!token) {
            return next({ name: 'login' });
        }
        if (!otpVerified) {
            return next({ name: 'verify-otp' });
        }
    }

    // Guest-only route (login/register) — redirect jika sudah login
    if (to.meta.guest && token && otpVerified) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
