import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'

import App from './App.vue'
import router from './router'

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

axios.defaults.baseURL = import.meta.env.VITE_API_URL;
axios.defaults.headers.common['Accept'] = 'application/json';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'w8l1miksia8jy2aa5jgg',
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: 8081,
    wssPort: 8081,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
