import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import PublicLayout from "../layouts/PublicLayout.vue";
import HomeView from "../views/Public/HomeView.vue";
import RoomView from "../views/Public/RoomView.vue";
import LoginView from "@/views/Public/LoginView.vue";
import RegisterView from "@/views/Public/RegisterView.vue";
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
        {
          path: 'profile',
          name: 'profile',
          component: ProfileView,
          meta: { requiresAuth: true }
        },
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
          next({ name: 'home' });
        }
      },
      children: [
        { path: '', redirect: '/manager/bookings' },
        { path: 'bookings', name: 'manager.bookings', component: ManagerBookingsView },
        { path: 'reviews', name: 'manager.reviews', component: ManagerReviewsView },
      ]
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
      return next({ name: 'home' });
    }
  }
  if ((to.name === 'login' || to.name === 'register') && authStore.isAuthenticated) {
    return next({ name: 'home' });
  }

  next();
});

export default router
