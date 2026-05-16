# Jarvis Designs

A full-stack custom apparel and design e-commerce platform built with **Vue 3** (frontend) and **Laravel 11** (backend). Users can browse products, use AI-generated design tools, place orders, and manage payments, while admins have a dedicated dashboard for order, inventory, and report management.

---

## Tech Stack

| Layer | Technologies |
|---|---|
| **Frontend** | Vue 3, TypeScript, Vite, TailwindCSS v4, Pinia, TanStack Vue Query, Vue Router |
| **UI Libraries** | PrimeVue, Flowbite, Headless UI, Heroicons |
| **Forms & Validation** | Vee-Validate, Yup |
| **HTTP & Real-time** | Axios, Laravel Echo, Pusher.js |
| **Charts** | Chart.js, vue-chartjs |
| **Backend** | Laravel 11, PHP 8.2+, Laravel Sanctum, Laravel Reverb, Laravel Queues |
| **Storage** | AWS S3, Flysystem |
| **Payments** | PayMongo, HitPay |
| **Export** | DomPDF (PDF generation) |
| **Monitoring** | Sentry |
| **Deployment** | Docker, Nginx, GitHub Actions |

---

## Project Structure

```
Jarvis-Design/
├── .github/
│   ├── actions/          # Reusable GitHub Actions
│   └── workflows/        # CI, Docker build, and production deployment pipelines
│
├── client/               # Vue 3 SPA (frontend)
└── server/               # Laravel 11 REST API (backend)
```

---

## Frontend (`/client/src`)

```
src/
├── api/            # Typed Axios functions organized by HTTP method (get/, post/, put/)
├── assets/         # Static assets (images, icons, fonts)
├── components/     # Reusable UI components (modals, dialogs, tables, navbar, sidebar)
├── composables/    # Vue composables for shared logic (auth, payments, navigation)
├── constants/      # App-wide enums and constant values
├── helper/         # Pure utility/helper functions
├── layout/         # Page layout wrapper components
├── router/         # Vue Router config and navigation guards
├── services/       # Third-party service setup (Laravel Echo / WebSocket)
├── stores/         # Pinia state stores (user session, filters)
├── themes/         # PrimeVue theme customization
├── types/          # TypeScript interfaces and type definitions
├── utils/          # Shared utility functions
└── views/
    ├── users/      # Customer-facing pages (home, designs, cart, orders, chat, auth)
    └── admin/      # Admin dashboard pages (products, orders, reports, materials, chat)
```

---

## Backend (`/server`)

```
server/
├── routes/         # API, web, broadcasting, and console route definitions
├── config/         # Laravel config files (auth, cors, database, S3, payments, reverb)
│
└── app/
    ├── Http/
    │   ├── Controllers/  # Request handlers (User, Designs, Payment, Cart, Chat, Dashboard, etc.)
    │   └── Requests/     # Form request validation classes
    ├── Models/           # Eloquent models (User, Products, Orders, Payments, Messages, etc.)
    ├── Services/         # Business logic layer (PaymentService, DesignsService, DashboardService, etc.)
    ├── Traits/           # Reusable PHP traits (S3 attachments, order helpers, sales calc)
    ├── Jobs/             # Queue jobs (payment processing, order confirmation email)
    ├── Events/           # Broadcasting events (chat messages, order status, notifications)
    ├── Mail/             # Mailable classes (email verification, order confirmation, password reset)
    ├── Interfaces/       # PHP contracts and interfaces
    └── Providers/        # Laravel service providers

database/
├── migrations/     # 45 migration files covering all tables
├── factories/      # Model factories for testing
└── seeders/        # Database seed classes
```

---

## Key Features

- **AI Design Generation** — Users prompt AI to generate custom apparel designs
- **Product Customization** — Size, style, color, and sublimation attribute selection
- **Payments** — PayMongo (GCash/card), HitPay, and QR code payment flows
- **Real-Time Chat** — Customer ↔ Admin messaging via Laravel Reverb (WebSocket)
- **Order Management** — Full order lifecycle tracking with status logs and email notifications
- **Admin Dashboard** — KPIs, sales charts, and PDF report exports
- **Authentication** — Sanctum cookie-based SPA auth, email verification, and password reset

---

## Running Locally

### Frontend
```bash
cd client
npm install
npm run dev
```

### Backend
```bash
cd server
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
php artisan reverb:start   # WebSocket server
php artisan queue:work     # Background job worker
```

### Docker
```bash
cd server
docker-compose up --build
```
