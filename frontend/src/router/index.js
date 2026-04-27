import {createRouter, createWebHistory} from 'vue-router';
import {useAuthStore} from '@/stores/auth';
import PublicLayout from "../layouts/PublicLayout.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import ManagerLayout from '../layouts/ManagerLayout.vue';
import HomeView from "../views/Public/HomeView.vue";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            component: PublicLayout,
            children: [
                {path: '', name: 'home', component: HomeView},
                {path: 'login', name: 'login', component: () => import('@/views/Auth/LoginView.vue')},
                {path: 'register', name: 'register', component: () => import('@/views/Auth/RegisterView.vue')},
                {
                    path: 'auth/callback',
                    name: 'auth.callback',
                    component: () => import('@/views/Auth/AuthCallbackView.vue')
                },
                {
                    path: 'forgot-password',
                    name: 'forgot-password',
                    component: () => import('@/views/Auth/ForgotPassword.vue')
                },
                {
                    path: 'reset-password',
                    name: 'reset-password',
                    component: () => import('@/views/Auth/ResetPassword.vue')
                },
                {
                    path: 'profile',
                    name: 'profile',
                    component: () => import('@/views/Public/ProfileView.vue'),
                    meta: {requiresAuth: true}
                },
                {path: 'about', name: 'about', component: () => import('@/views/Public/AboutView.vue')},
                {path: 'contacts', name: 'contacts', component: () => import('@/views/Public/ContactView.vue')},
                {path: 'success', name: 'success', component: () => import('@/views/Public/SuccessView.vue')},
                {path: 'cancel', name: 'cancel', component: () => import('@/views/Public/CancelView.vue')},
                {path: '404', name: 'not-found', component: () => import('@/views/Public/NotFoundView.vue')},
                {path: '/:slug', name: 'room.show', component: () => import('../views/Public/RoomView.vue')},
            ]
        },
        {
            path: '/admin',
            component: AdminLayout,
            meta: {requiresAuth: true, role: 'admin'},
            children: [
                {path: '', name: 'admin.dashboard', component: () => import('../views/Admin/DashboardView.vue')},
                {path: 'rooms', name: 'admin.rooms', component: () => import('../views/Admin/RoomsAdminView.vue')},
                {
                    path: 'rooms/create',
                    name: 'admin.rooms.create',
                    component: () => import('../views/Admin/RoomFormView.vue')
                },
                {
                    path: 'rooms/edit/:id',
                    name: 'admin.rooms.edit',
                    component: () => import('../views/Admin/RoomFormView.vue')
                },
                {
                    path: 'bookings',
                    name: 'admin.bookings',
                    component: () => import('../views/Admin/BookingsAdminView.vue')
                },
                {path: 'users', name: 'admin.users', component: () => import('../views/Admin/UsersAdminView.vue')},
            ]
        },
        {
            path: '/manager',
            component: ManagerLayout,
            beforeEnter: (to, from) => {
                const authStore = useAuthStore();
                if (authStore.user && (authStore.user.role === 'manager' || authStore.user.role === 'admin')) {
                    return true;
                } else {
                    return {name: 'not-found'};
                }
            },
            children: [
                {path: '', redirect: '/manager/bookings'},
                {
                    path: 'bookings',
                    name: 'manager.bookings',
                    component: () => import('../views/Manager/ManagerBookingsView.vue')
                },
                {
                    path: 'reviews',
                    name: 'manager.reviews',
                    component: () => import('@/views/Manager/ManagerReviewsView.vue')
                },
            ]
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: {name: 'not-found'}
        }
    ]
});

router.beforeEach(async (to, from) => {
    const authStore = useAuthStore();

    if (authStore.token && !authStore.user) {
        await authStore.fetchUser();
    }
    if (to.meta.requiresAuth) {
        if (!authStore.isAuthenticated) {
            return {name: 'login'};
        }
        if (to.meta.role && authStore.user?.role !== to.meta.role) {
            return {name: 'not-found'};
        }
    }
    const guestOnlyRoutes = ['login', 'register', 'forgot-password', 'reset-password'];
    if (guestOnlyRoutes.includes(to.name) && authStore.isAuthenticated) {
        return {name: 'home'};
    }

    return true;
});

export default router;
