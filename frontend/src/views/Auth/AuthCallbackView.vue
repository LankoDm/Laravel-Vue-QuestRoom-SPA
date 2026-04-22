<script setup>
import {onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {useAuthStore} from '@/stores/auth.js';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

/**
 * Handle the OAuth callback on component mount.
 * Extracts the token from the URL, sets up the authenticated session,
 * and fetches the user profile.
 */
onMounted(async () => {
    const fragmentToken = new URLSearchParams(window.location.hash.slice(1)).get('token');
    const token = fragmentToken || route.query.token;

    if (token) {
        localStorage.setItem('token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        authStore.token = token;

        try {
            await authStore.fetchUser();
            router.replace({name: 'home'});
        } catch (error) {
            console.error('Error fetching Google profile:', error);
            router.replace({name: 'login'});
        }
    } else {
        // Redirect to login if no token is present in the URL
        router.replace({name: 'login'});
    }
});
</script>

<template>
    <div class="min-h-[60vh] flex flex-col items-center justify-center">
        <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4"></div>
        <h2 class="text-2xl font-black text-text">Авторизація через Google</h2>
        <p class="text-gray-500 mt-2">Зачекайте мить, ми налаштовуємо ваш профіль</p>
    </div>
</template>
