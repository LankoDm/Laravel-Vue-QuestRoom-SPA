import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import PublicLayout from "../layouts/PublicLayout.vue";
import HomeView from "../views/Public/HomeView.vue";
import RoomView from "../views/Public/RoomView.vue";
import LoginView from "@/views/Auth/LoginView.vue";
import RegisterView from "@/views/Auth/RegisterView.vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import DashboardView from '../views/Admin/DashboardView.vue';
import RoomsAdminView from '../views/Admin/RoomsAdminView.vue';
import RoomFormView from '../views/Admin/RoomFormView.vue';
import BookingsAdminView from '../views/Admin/BookingsAdminView.vue';
import UsersAdminView from '../views/Admin/UsersAdminView.vue';
import ManagerLayout from '../layouts/ManagerLayout.vue';
import ManagerBookingsView from '../views/Manager/ManagerBookingsView.vue';
import ManagerReviewsView from "@/views/Manager/ManagerReviewsView.vue";
import ProfileView from "@/views/Public/ProfileView.vue";
import AboutView from "@/views/Public/AboutView.vue";
import ContactView from "@/views/Public/ContactView.vue";
import SuccessView from "@/views/Public/SuccessView.vue";
import CancelView from "@/views/Public/CancelView.vue";
import AuthCallbackView from "@/views/Auth/AuthCallbackView.vue";
import ForgotPassword from "@/views/Auth/ForgotPassword.vue";
import ResetPassword from "@/views/Auth/ResetPassword.vue";
import NotFoundView from "@/views/Public/NotFoundView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        { path: '', name: 'home', component: HomeView },
        { path: '/:slug', name: 'room.show', component: RoomView },
        { path: 'login', name: 'login', component: LoginView },
        { path: 'register', name: 'register', component: RegisterView },
        { path: 'auth/callback', name: 'auth.callback', component: AuthCallbackView },
        { path: 'forgot-password', name: 'forgot-password', component: ForgotPassword },
        { path: 'reset-password', name: 'reset-password', component: ResetPassword },
        {
          path: 'profile',
          name: 'profile',
          component: ProfileView,
          meta: { requiresAuth: true }
        },
        { path: 'about', name: 'about', component: AboutView },
        { path: 'contacts', name: 'contacts', component: ContactView },
        { path: 'success', name: 'success', component: SuccessView },
        { path: 'cancel', name: 'cancel', component: CancelView },
        { path: '404', name: 'not-found', component: NotFoundView },
      ]
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, role: 'admin' },
      children: [
        { path: '', name: 'admin.dashboard', component: DashboardView },
        { path: 'rooms', name: 'admin.rooms', component: RoomsAdminView },
        { path: 'rooms/create', name: 'admin.rooms.create', component: RoomFormView },
        { path: 'rooms/edit/:id', name: 'admin.rooms.edit', component: RoomFormView },
        { path: 'bookings', name: 'admin.bookings', component: BookingsAdminView },
        { path: 'users', name: 'admin.users', component: UsersAdminView },
      ]
    },
    {
      path: '/manager',
      component: ManagerLayout,
      beforeEnter: (to, from, next) => {
        const authStore = useAuthStore();
        if (authStore.user && (authStore.user.role === 'manager' || authStore.user.role === 'admin')) {
          next();
        } else {
          next({ name: 'not-found' });
        }
      },
      children: [
        { path: '', redirect: '/manager/bookings' },
        { path: 'bookings', name: 'manager.bookings', component: ManagerBookingsView },
        { path: 'reviews', name: 'manager.reviews', component: ManagerReviewsView },
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'not-found' }
    }
  ]
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  if (authStore.token && !authStore.user) {
    await authStore.fetchUser();
  }
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      return next({ name: 'login' });
    }
    if (to.meta.role && authStore.user?.role !== to.meta.role) {
      return next({ name: 'not-found' });
    }
  }
  const guestOnlyRoutes = ['login', 'register', 'forgot-password', 'reset-password'];
  if (guestOnlyRoutes.includes(to.name) && authStore.isAuthenticated) {
    return next({ name: 'home' });
  }

  next();
});

export default router
