import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'

import App from './App.vue'
import router from './router'

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import { useAuthStore } from '@/stores/auth.js';

axios.defaults.baseURL = import.meta.env.VITE_API_URL;
axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && (error.response.status === 401 || error.response.status === 403)) {
            const authStore = useAuthStore();

            authStore.logoutLocally();

            router.push({ name: 'login' });
        }

        return Promise.reject(error);
    }
);

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'eu',
    forceTLS: true
});

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
