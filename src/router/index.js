import { createRouter, createWebHistory } from 'vue-router';
import PublicLayout from "../layouts/PublicLayout.vue";
import HomeView from "../views/Public/HomeView.vue";
import RoomView from "../views/Public/RoomView.vue";
import LoginView from "@/views/Public/LoginView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        {
          path: '',
          name: 'home',
          component: HomeView
        },
        {
          path: 'rooms/:id',
          name: 'room.show',
          component: RoomView
        },
        {
          path: 'login',
          name: 'login',
          component: LoginView
        }
      ]
    },
    // {
    //   path: '/admin',
    //   name: 'admin',
    //   component: AdminDashboard
    // }
  ],
})

export default router
