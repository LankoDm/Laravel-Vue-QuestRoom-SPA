<script setup>
import {ref, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

// Reactive form state
const form = ref({
    email: '',
    token: '',
    password: '',
    password_confirmation: ''
});
const message = ref('');
const errorMessage = ref('');
const isLoading = ref(false);

/**
 * Extract email and reset token from URL query parameters on mount.
 */
onMounted(() => {
    form.value.email = route.query.email || '';
    form.value.token = route.query.token || '';
});

/**
 * Submit the new password to complete the reset process.
 * Redirects to the login page after a short delay upon success.
 */
const submit = async () => {
    isLoading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        const response = await axios.post('/reset-password', form.value);
        message.value = response.data.message;

        // Redirect to login after 3 seconds
        setTimeout(() => {
            router.push('/login');
        }, 3000);
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Reset failed. The link may have expired.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="max-w-md mx-auto mt-20 p-6 bg-white rounded-3xl shadow-sm border border-gray-100">
        <h2 class="text-2xl font-black text-center mb-6">Створення нового пароля</h2>
        <div v-if="message" class="mb-4 p-4 bg-green-50 text-green-600 rounded-xl text-sm font-medium">
            {{ message }} Перенаправлення на сторінку входу
        </div>
        <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 text-red-600 rounded-xl text-sm font-medium">
            {{ errorMessage }}
        </div>
        <form @submit.prevent="submit" class="space-y-4" v-if="!message">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="form.email" type="email" readonly
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Новий пароль</label>
                <input v-model="form.password" type="password" required minlength="8"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Підтвердіть пароль</label>
                <input v-model="form.password_confirmation" type="password" required minlength="8"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none">
            </div>
            <button type="submit" :disabled="isLoading"
                    class="w-full bg-primary text-white font-bold py-3 px-4 rounded-xl hover:bg-purple-600 transition-colors disabled:opacity-50">
                {{ isLoading ? 'Збереження' : 'Зберегти новий пароль' }}
            </button>
        </form>
    </div>
</template>
