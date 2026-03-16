<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const users = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const fetchUsers = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get(`http://localhost:8080/api/users?email=${searchQuery.value}`);
    users.value = response.data.data || response.data;
  } catch (error) {
    console.error('Помилка завантаження користувачів:', error);
  } finally {
    isLoading.value = false;
  }
};
const updateRole = async (user, newRole) => {
  const oldRole = user.role;
  user.role = newRole;
  try {
    await axios.patch(`http://localhost:8080/api/users/${user.id}/role`, { role: newRole });
  } catch (error) {
    console.error('Помилка оновлення ролі:', error);
    user.role = oldRole;
    alert('Не вдалося оновити роль. Перевірте консоль.');
  }
};

onMounted(() => {
  fetchUsers();
});
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-black text-text">Користувачі та Ролі</h1>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-sm border border-secondary mb-8">
      <form @submit.prevent="fetchUsers" class="flex gap-4 items-end">
        <div class="flex-1">
          <label class="block text-sm font-bold text-gray-700 mb-2">Знайти за Email</label>
          <input
              v-model="searchQuery"
              type="email"
              placeholder="Введіть email користувача"
              class="w-full px-4 py-3 rounded-xl border border-secondary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-colors bg-gray-50">
        </div>
        <button type="submit" class="bg-primary hover:bg-purple-500 text-white px-8 py-3 rounded-xl font-bold shadow-md transition-colors h-[50px]">
          Знайти
        </button>
      </form>
    </div>

    <div v-if="isLoading" class="text-center py-12 text-gray-500 animate-pulse">
      Пошук користувачів
    </div>
    <div v-else class="bg-white rounded-3xl shadow-sm border border-secondary overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
          <tr class="bg-secondary/30 border-b border-secondary text-gray-500 font-bold uppercase text-xs tracking-wider">
            <th class="p-4 pl-6">ID / Ім'я</th>
            <th class="p-4">Email</th>
            <th class="p-4">Поточна Роль</th>
            <th class="p-4">Змінити Роль</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
            <td class="p-4 pl-6">
              <div class="font-bold text-text">#{{ user.id }}</div>
              <div class="font-bold text-primary mt-1">{{ user.name }}</div>
            </td>
            <td class="p-4 text-gray-600 font-medium">
              {{ user.email }}
            </td>
            <td class="p-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold tracking-wide"
                      :class="user.role === 'admin' ? 'bg-red-100 text-red-700' : (user.role === 'manager' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700')">
                  {{ user.role.toUpperCase() }}
                </span>
            </td>
            <td class="p-4">
              <select
                  :value="user.role"
                  @change="updateRole(user, $event.target.value)"
                  class="px-3 py-2 rounded-lg border border-secondary focus:ring-2 focus:ring-primary outline-none bg-white font-bold text-sm cursor-pointer">
                <option value="user">USER (Клієнт)</option>
                <option value="manager">MANAGER (Менеджер)</option>
                <option value="admin">ADMIN (Адмін)</option>
              </select>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
      <div v-if="users.length === 0" class="p-8 text-center text-gray-500">
        Користувачів не знайдено.
      </div>
    </div>
  </div>
</template>