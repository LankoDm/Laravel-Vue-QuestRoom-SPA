# QuestRoom SPA

Full-stack web application for booking quest rooms.

## Screenshots

1. Home page
<img width="1912" height="960" alt="Снимок экрана от 2026-04-27 23-11-14" src="https://github.com/user-attachments/assets/8f2d0bf1-d3bc-4c28-90fe-cd191de53533" />

2. Room details page
<img width="1912" height="960" alt="Снимок экрана от 2026-04-27 23-12-02" src="https://github.com/user-attachments/assets/60490972-01a5-4008-bcf4-99c7c28721cc" />

3. Booking flow
<img width="1912" height="960" alt="Снимок экрана от 2026-04-27 23-12-49" src="https://github.com/user-attachments/assets/2b753d80-e519-4685-afd6-8f64a940e3c2" />

4. User profile
<img width="1912" height="960" alt="изображение" src="https://github.com/user-attachments/assets/357ec5e9-7c46-42da-9745-f9684f01c84e" />

5. Admin dashboard
<img width="1912" height="960" alt="изображение" src="https://github.com/user-attachments/assets/ef0ee3d4-e140-4a18-88e1-c9fb99c43d8c" />

6. Manager bookings
<img width="1912" height="960" alt="изображение" src="https://github.com/user-attachments/assets/31c53fd2-28d4-4401-bf7e-ba4cb8b1c4b1" />

7. Mobile view
<img width="412" height="830" alt="изображение" src="https://github.com/user-attachments/assets/25606efd-fce2-4a40-9412-ef35495e8db3" />


## Stack

- Backend: Laravel 12 (Sanctum, Reverb, Stripe, Socialite)
- Frontend: Vue 3 + Vite + Pinia + Vue Router
- Database: MySQL
- Cache/Queue: Redis
- Optional local infrastructure: Docker Compose

## Repository Structure

- backend: Laravel API and business logic
- frontend: Vue SPA
- docker-compose.yml: local multi-container setup

## Environment Setup

There are three environment example files in this repo:

- .env.example: variables used by docker-compose services
- backend/.env.example: Laravel API variables
- frontend/.env.example: Vue client variables

### 1) Root .env (for docker-compose)

Copy and fill Stripe CLI key:

```bash
cp .env.example .env
```

Required variable:

- STRIPE_SECRET: stripe secret key used by `stripe/stripe-cli` container

### 2) Backend .env

```bash
cd backend
cp .env.example .env
php artisan key:generate
```

Important variables to fill for real integrations:

- DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET
- GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI
- CLOUDINARY_URL, CLOUDINARY_UPLOAD_PRESET
- MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS
- REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET

### 3) Frontend .env

```bash
cd frontend
cp .env.example .env
```

Required variables:

- VITE_API_URL: backend API URL, example `http://localhost:8080/api`
- VITE_PUSHER_APP_KEY: key for Echo/Pusher protocol client
- VITE_PUSHER_APP_CLUSTER: default `eu`

## Run Locally (without Docker)

### Backend

```bash
cd backend
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8080
```

Optional workers in separate terminals:

```bash
cd backend
php artisan queue:work
php artisan reverb:start --host=0.0.0.0 --port=8081
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Run with Docker Compose

```bash
cp .env.example .env
docker compose up --build
```

Default ports:

- Frontend: http://localhost:5173
- Backend API: http://localhost:8080
- Reverb: http://localhost:8081
- MySQL: 3306
- Redis: 6379

## Tests

```bash
cd backend
php artisan test
```
