<template>
  <div class="py-16 px-6 max-w-6xl mx-auto">
    <div class="text-center mb-16">
      <h1 class="text-5xl font-black text-text mb-4">Наші контакти</h1>
      <p class="text-xl text-gray-500 font-medium">Завжди раді бачити вас у гостях. Залишилися питання? Дзвоніть!</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-secondary text-center">
          <div class="w-14 h-14 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
          </div>
          <h3 class="font-black text-xl mb-2 text-text">Адреса</h3>
          <p class="text-gray-500 font-medium">м. Чернігів, пр-т Миру, 20<br>(Вхід з двору)</p>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-sm border border-secondary text-center">
          <div class="w-14 h-14 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
          </div>
          <h3 class="font-black text-xl mb-2 text-text">Телефон</h3>
          <p class="text-gray-500 font-medium">+38 (099) 123-45-67<br>Щодня з 10:00 до 23:00</p>
        </div>
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-secondary text-center">
          <div class="w-14 h-14 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
          </div>
          <h3 class="font-black text-xl mb-2 text-text">Електронна пошта</h3>
          <p class="text-gray-500 font-medium">hello@oneaquest.cn.ua<br>Для співпраці та пропозицій</p>
        </div>
      </div>
      <div class="lg:col-span-2 bg-gray-100 rounded-3xl overflow-hidden shadow-sm border border-secondary relative h-[400px] lg:h-auto">
        <iframe
            src="https://maps.google.com/maps?q=Чернігів,+пр-т+Миру,+20&t=&z=15&ie=UTF8&iwloc=&output=embed"
            class="absolute inset-0 w-full h-full border-0"
            :allowfullscreen="true"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>

    </div>
  </div>
</template>
