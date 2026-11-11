# PayBen – Decoupled Fintech Architecture

A production-grade, full-stack UPI payment, mobile recharge, and utility bill payment application featuring a modern **React.js SPA** frontend and a layered **PHP 8 REST API** backend with **MySQL**.

![React](https://img.shields.io/badge/React-18.3-blue?logo=react&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-5.4-purple?logo=vite&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind-3.4-38bdf8?logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777bb4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

> **Note**: All financial transactions, recharges, and bill settlements are simulated in an isolated environment with zero real money risk.

---

## Architecture Overview

PayBen adopts a strict two-tier decoupled architecture:

```
PayBen/
├── frontend/                     # React.js Single Page Application (SPA)
│   ├── public/                   # Static assets & favicon
│   ├── src/
│   │   ├── assets/               # CSS styles & design tokens
│   │   ├── components/           # Reusable UI (Navbar, Sidebar, Modals)
│   │   ├── context/              # Global state (AuthContext, ToastContext)
│   │   ├── hooks/                # Custom React hooks
│   │   ├── layouts/              # MainLayout & AdminLayout shells
│   │   ├── pages/
│   │   │   ├── auth/             # Login & Register views
│   │   │   ├── admin/            # Admin analytics, users, and audit log
│   │   │   ├── dashboard/        # GPay-inspired UPI dashboard
│   │   │   ├── bills/            # Electricity, Water, and Gas bill pay
│   │   │   ├── recharge/         # Prepaid Mobile & DTH recharge flows
│   │   │   ├── wallet/           # Digital wallet, ledger, and UPI ID
│   │   │   ├── transactions/     # Filterable statements and receipt view
│   │   │   └── profile/          # User account settings
│   │   ├── routes/               # AppRoutes with ProtectedRoute & AdminRoute
│   │   ├── services/             # Axios API client modules
│   │   ├── App.jsx               # App component
│   │   └── main.jsx              # Application bootstrap
│   ├── .env                      # Vite environment variables
│   ├── package.json              # NPM dependencies
│   └── vite.config.js            # Vite configuration & dev proxy
│
├── backend/                      # Layered PHP REST API Engine
│   ├── api/                      # REST endpoints (JSON responses only)
│   │   ├── admin/                # Admin auth, telemetry, and audit APIs
│   │   ├── auth/                 # Login, register, me, logout endpoints
│   │   ├── bills/                # Electricity, water, gas fetch & pay APIs
│   │   ├── recharge/             # Operators, plans, mobile, DTH APIs
│   │   ├── transactions/         # Statements, receipt details, dashboard summary
│   │   └── wallet/               # Balance, add-money, transfer, scratch card
│   ├── config/                   # Config loader & singleton PDO database
│   ├── controllers/              # Application business logic controllers
│   ├── models/                   # Data access objects (User, Wallet, Transaction...)
│   ├── services/                 # Payment & ledger services
│   ├── middleware/               # CORS preflight & auth session middleware
│   ├── database/                 # Schema & seed data (payben_db.sql)
│   ├── index.php                 # Healthcheck & API status endpoint
│   └── .env                      # Database credentials & CORS origins
│
├── docs/                         # Architecture, API specifications, and setup docs
├── .gitignore                    # Git exclusions
├── LICENSE                       # MIT License
└── README.md
```

---

## Features

- **GPay-Style Dashboard** — Clean UPI balance card, 6 quick circular actions, network contacts carousel, and recent activity feed.
- **Peer-to-Peer UPI Transfers** — Send money by mobile number, email, or `@payben` UPI handle with atomic double-entry balance locking.
- **Scratch Card Cashback** — Interactive scratch card rewards (₹5–₹50) with confetti celebration.
- **Mobile & DTH Recharge** — Operator selection, tabbed plan browsing (Unlimited, 1.5GB/day, Data packs), and instant checkout.
- **Utility Bill Payments** — Electricity, water, and piped gas bill fetch by consumer number with receipt generation.
- **Digital Wallet** — Top-up balance, view statements, and manage personal UPI handle.
- **Admin Control Panel** — System analytics, user activation/suspension, and transaction audit logs.

---

## Quick Start Guide

### 1. Prerequisites
- **Node.js** 18+ and **npm**
- **PHP** 8.0+ with `pdo_mysql` extension enabled
- **MySQL** 5.7+ or 8.0+

### 2. Database Setup
Import the database schema into MySQL:
```bash
mysql -u root -p payben_db < backend/database/payben_db.sql
```

Ensure `backend/.env` points to your MySQL database:
```env
DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=payben_db
DB_USER=root
DB_PASS=
```

### 3. Run Backend (PHP REST API)
```bash
cd backend
php -S 127.0.0.1:8000
```
Backend API is live at `http://127.0.0.1:8000` (check health status at `http://127.0.0.1:8000/index.php`).

### 4. Run Frontend (React + Vite)
```bash
cd frontend
npm install
npm run dev
```
Open **`http://localhost:5173`** in your browser.

---

## Test Accounts

| Role | Username / Email | Password | Initial Balance |
| :--- | :--- | :--- | :--- |
| User 1 | `user1@test.com` | `password123` | ₹5,000.00 |
| User 2 | `user2@test.com` | `password123` | ₹5,000.00 |
| Admin | `admin@payben.com` | `admin123` | N/A |

*New registrations automatically receive a ₹500.00 welcome bonus!*

---

## REST API Endpoints Summary

All backend responses return standard JSON: `{ "success": boolean, "message": string, "data": any }`.

### Authentication
- `POST /api/auth/login.php` — Authenticate and receive session token
- `POST /api/auth/register.php` — Create account with ₹500 welcome credit
- `GET /api/auth/me.php` — Current authenticated user profile
- `POST /api/auth/logout.php` — Invalidate session

### Wallet & UPI
- `GET /api/wallet/balance.php` — Current wallet balance and UPI handle
- `POST /api/wallet/add-money.php` — Top-up wallet funds
- `POST /api/wallet/transfer.php` — P2P transfer by recipient identifier
- `POST /api/wallet/scratch-card.php` — Claim mystery cashback reward
- `GET /api/wallet/history.php` — Double-entry ledger audit trail

### Recharges & Utilities
- `GET /api/recharge/operators.php?type=mobile|dth` — List active providers
- `GET /api/recharge/plans.php?operator_id=X` — Tariff plans for operator
- `POST /api/recharge/mobile.php` — Execute prepaid mobile recharge
- `POST /api/recharge/dth.php` — Execute DTH subscription payment
- `GET /api/bills/{electricity|water|gas}.php?action=fetch&provider_id=X&consumer_number=Y` — Fetch bill
- `POST /api/bills/{electricity|water|gas}.php` — Pay utility bill

### Transactions
- `GET /api/transactions/dashboard.php` — Dashboard metrics & recent feed
- `GET /api/transactions/list.php?type=X&status=Y` — Filtered transaction log
- `GET /api/transactions/details.php?id=X` — Single transaction receipt

### Admin Portal
- `POST /api/admin/login.php` — Administrator authentication
- `GET /api/admin/dashboard.php` — Platform volume, users, and holdings telemetry
- `GET /api/admin/users.php` — User directory with wallet balances
- `POST /api/admin/users.php` — Toggle user suspension status
- `GET /api/admin/transactions.php` — System-wide transaction audit log

---

## Security Implementations

- **Strict CORS Policy** with explicit origin validation (`http://localhost:5173`) and credential support.
- **SQL Injection Prevention** with 100% parameterized PDO statements and `emulate_prepares = false`.
- **Concurrency Protection** using `SELECT ... FOR UPDATE` row-level locks on wallet operations inside atomic transactions.
- **Password Security** using standard bcrypt hashing (`PASSWORD_BCRYPT`).
- **Session Protection** via `HttpOnly`, `SameSite=Lax`, and session regeneration on authentication.

---

## License

MIT © 2026 PayBen Platform.
