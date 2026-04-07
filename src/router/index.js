import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/Public/HomeView.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    // {
    //   path: '/admin',
    //   name: 'admin',
    //   component: AdminDashboard
    // }
  ],
})

export default router
