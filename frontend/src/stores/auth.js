import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import axios from 'axios';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', () => {
    const router = useRouter();
    const user = ref(null);
    const token = ref(localStorage.getItem('token') || null);
    const isAuthenticated = computed(() => !!token.value);
    const isAdmin = computed(() => user.value?.role === 'admin');
    const isManager = computed(() => user.value?.role === 'manager');

    const fetchUser = async () => {
        if(!token.value) return;
        try {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
            const response = await axios.get('/user');
            user.value = response.data;
        }catch(error){
            console.error('Помилка відновлення сесії:', error);
            user.value = null;
            token.value = null;
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        }
    };
    const register = async (userData) => {
        try {
            const response = await axios.post('/register', userData);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('token', token.value);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
            return true;
        } catch (error) {
            console.error('Помилка реєстрації:', error);
            throw error;
        }
    };
    const login = async (credentials) => {
        try {
            const response = await axios.post('/login', credentials);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('token', token.value);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
            return true;
        } catch (error) {
            console.error('Помилка авторизації:', error);
            throw error;
        }
    };

    const logout = async () => {
        try {
            if (token.value) {
                await axios.post('/logout', {}, {
                    headers: { Authorization: `Bearer ${token.value}` }
                });
            }
        } catch (error) {
            console.error('Помилка при виході:', error);
        } finally {
            user.value = null;
            token.value = null;
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
            router.push({ name: 'login' });
        }
    };

    const logoutLocally = () => {
        user.value = null;
        token.value = null;
        localStorage.removeItem('token');
        delete axios.defaults.headers.common['Authorization'];
    };

    return { user, token, isAuthenticated, isAdmin, isManager, fetchUser, login, logout, register, logoutLocally };
});
