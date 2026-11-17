<div align="center">

# ⚡ PayBen 2.0 — All-in-One Fintech Super App

### **Modern Smart UPI, Travel, Shopping, Food, Mobility & Multi-Utility Ecosystem**

*A production-grade, full-stack digital services super app — bringing together UPI wallet payments, recharges, BBPS utilities, train/bus/flight/hotel travel bookings, shopping discovery, food delivery, and Uber/Rapido ride booking under a unified Neo-Fintech Hybrid experience.*

<br/>

[![React](https://img.shields.io/badge/React-18.3-61dafb?style=for-the-badge&logo=react&logoColor=black)](https://react.dev/)
[![Vite](https://img.shields.io/badge/Vite-5.4-646cff?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev/)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-3.4-38bdf8?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3-777bb4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479a1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-emerald?style=for-the-badge)](LICENSE)

<br/>

> 🛡️ **Zero-Paid Sandbox Architecture**: All financial transactions, travel bookings, and utility payments operate within a self-contained local simulation sandbox using immutable double-entry ledger bookkeeping (`BIGINT` integer paise). External services (Flipkart, Myntra, Swiggy, Uber, Rapido, IRCTC) operate with verified official partner deep-link redirections without capturing user credentials or inventing fake confirmations.

</div>

---

## 🌟 PayBen 2.0 Super App Ecosystem

| Service Domain | Capabilities | Integration Type |
|---|---|---|
| **⚡ Payments & UPI** | Wallet balance, P2P transfers, simulated checkout, QR scanner & camera generator, PIN lock screen | **SANDBOX (Local Ledger)** |
| **📱 Recharges & Utilities** | Mobile prepaid/postpaid, DTH, Electricity, Water, Piped Gas, Broadband & FTTH | **SANDBOX (BBPS Simulator)** |
| **🚆 Travel & Bookings** | Train tickets (Vande Bharat, Rajdhani), Intercity buses (IntrCity, Zingbus, VRL), Flights (IndiGo, Air India), Hotel rooms | **SANDBOX + Official Redirect (IRCTC, redBus)** |
| **🛍️ Online Shopping** | Marketplace directory (Flipkart, Myntra, Meesho; Jabong disabled by default) | **EXTERNAL_REDIRECT (Verified)** |
| **🍔 Food & Grocery** | On-demand dining & 10-minute grocery pantry via Swiggy | **EXTERNAL_REDIRECT (Verified)** |
| **🚗 Ride Booking & Mobility** | Cabs, Auto Rickshaws & Bike Taxis via Uber & Rapido with route fare estimators | **EXTERNAL_REDIRECT (Deep-Link)** |
| **🛡️ Risk & Fraud Engine** | Rule-based velocity checks, IP tracking, double-entry ledger verification | **INTERNAL (Rule-Based)** |
| **👑 Admin Oversight** | Users, ledger transactions, travel reservations, partner merchants, CSV reporting | **ROLE-BASED PORTAL** |

---

## 🌟 Key Highlights

<table>
  <tr>
    <td width="50%">
      <h3>⚡ Smart UPI Dashboard</h3>
      <ul>
        <li>Google Pay-inspired frictionless interface</li>
        <li>Personalized <code>@payben</code> UPI ID handle</li>
        <li>One-tap quick actions: Send, Request, Top-up, QR</li>
        <li>Recent network contacts carousel for instant P2P payments</li>
      </ul>
    </td>
    <td width="50%">
      <h3>🎁 Interactive Rewards Engine</h3>
      <ul>
        <li>Mystery scratch cards unlocked with every transaction</li>
        <li>Dynamic canvas confetti celebration</li>
        <li>Random cashback rewards (₹5 – ₹50) credited instantly</li>
        <li>Zero fees, instant double-entry wallet reflection</li>
      </ul>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <h3>📱 Telecom & DTH Recharges</h3>
      <ul>
        <li>Prepaid mobile recharge for Jio, Airtel, Vi, and BSNL</li>
        <li>Tabbed tariff browser (Unlimited, Daily Data, Top-ups)</li>
        <li>DTH subscription top-ups for Tata Play, Sun Direct, D2H</li>
        <li>Real-time subscriber validation & instant ledger deduction</li>
      </ul>
    </td>
    <td width="50%">
      <h3>💡 Multi-Utility Bill Payments</h3>
      <ul>
        <li>Electricity bill fetch & settlement across state boards</li>
        <li>Municipal corporation water supply bill lookup</li>
        <li>Piped natural gas & cylinder booking payments</li>
        <li>Downloadable and printable digital transaction receipts</li>
      </ul>
    </td>
  </tr>
</table>

---

## 🏗️ System Architecture

PayBen is architected around a strict decoupled design pattern: **Client-Side Rendering (CSR)** communicating through a **JSON REST API** layer with **Atomic Double-Entry Bookkeeping**.

```mermaid
flowchart TD
    subgraph Client ["Client Layer (React 18 + Vite)"]
        UI["Tailwind CSS UI Components"]
        Router["React Router v6"]
        AuthCtx["Auth & Toast State Context"]
        AxiosClient["Axios REST Client (Bearer + Credentials)"]
    end

    subgraph Gateway ["API Gateway & Routing (PHP 8.3)"]
        CORS["CORS Middleware (Origin & Preflight)"]
        AuthMid["Session & Token Auth Guard"]
        RouterEntry["REST Controllers Router"]
    end

    subgraph CoreEngine ["Backend Engine Layer"]
        AuthCtrl["AuthController"]
        WalletCtrl["WalletController"]
        RechargeCtrl["RechargeController"]
        BillCtrl["BillController"]
        AdminCtrl["AdminController"]
    end

    subgraph DataStore ["Persistence Layer (MySQL 8.0)"]
        DB[(payben_db)]
        Lock["FOR UPDATE Row Locks"]
        Ledger["Double-Entry Ledger"]
    end

    UI --> Router --> AuthCtx --> AxiosClient
    AxiosClient -->|"HTTP/JSON (port 8000)"| CORS
    CORS --> AuthMid --> RouterEntry
    RouterEntry --> AuthCtrl & WalletCtrl & RechargeCtrl & BillCtrl & AdminCtrl
    WalletCtrl & RechargeCtrl & BillCtrl --> Lock --> Ledger --> DB
```

---

## 📂 Repository Structure

```
PayBen/
│
├── frontend/                     # ⚛️ React 18 Single Page Application
│   ├── public/                   # Static icons & brand assets
│   ├── src/
│   │   ├── assets/               # CSS styles & design tokens
│   │   ├── components/           # Reusable UI widgets
│   │   │   ├── common/           # Navbar, Sidebar, Badges
│   │   │   └── modals/           # SendMoney, AddMoney, QrModal, ScratchCard
│   │   ├── context/              # Global state (AuthContext, ToastContext)
│   │   ├── layouts/              # MainLayout & AdminLayout shells
│   │   ├── pages/
│   │   │   ├── auth/             # Login & Register views
│   │   │   ├── admin/            # Analytics, Users, and Transactions
│   │   │   ├── dashboard/        # Main UPI & Wallet Dashboard
│   │   │   ├── bills/            # Electricity, Water, and Gas bill pay
│   │   │   ├── recharge/         # Mobile & DTH recharge flows
│   │   │   ├── wallet/           # Digital wallet & ledger audit trail
│   │   │   ├── transactions/     # Statements & receipt modals
│   │   │   └── profile/          # User profile settings
│   │   ├── routes/               # AppRoutes with ProtectedRoute guards
│   │   ├── services/             # Modular Axios API service layer
│   │   ├── App.jsx               # Root App component
│   │   └── main.jsx              # Vite entry point
│   ├── .env                      # Frontend environment config
│   ├── package.json              # NPM dependencies
│   ├── tailwind.config.js        # Custom fintech color palette
│   └── vite.config.js            # Vite build & reverse proxy
│
├── backend/                      # 🐘 Layered PHP REST API Backend
│   ├── api/                      # Public REST JSON endpoints
│   │   ├── admin/                # Telemetry, user management & audit logs
│   │   ├── auth/                 # Login, register, me, logout endpoints
│   │   ├── bills/                # Utility bill lookup & payment APIs
│   │   ├── recharge/             # Operators, plans, and recharge APIs
│   │   ├── transactions/         # Statements & dashboard summary APIs
│   │   └── wallet/               # Balance, transfer, top-up, scratch card
│   ├── config/                   # Config loader & singleton PDO database
│   ├── controllers/              # Business logic controllers
│   ├── models/                   # Database access objects (User, Wallet...)
│   ├── middleware/               # CORS preflight & auth verification
│   ├── database/                 # Schema & seeds (payben_db.sql)
│   ├── index.php                 # Healthcheck & API status endpoint
│   └── .env                      # Database credentials & CORS origins
│
├── docs/                         # Specifications & setup guides
├── .gitignore                    # Production git exclusions
├── LICENSE                       # MIT License
└── README.md
```

---

## ⚡ Quick Start

### 1. Prerequisites
- **Node.js** (v18+) and **npm**
- **PHP** (v8.0+) with `pdo_mysql` extension enabled
- **MySQL** (v5.7+ or v8.0+)

### 2. Database Initialization
Import the database schema and sample seed records into MySQL:

```bash
# Log in to MySQL and import schema
mysql -u root -p payben_db < backend/database/payben_db.sql
```

Verify your credentials in `backend/.env`:
```env
DB_HOST=127.0.0.1
DB_PORT=3307          # Or 3306 for standard MySQL
DB_NAME=payben_db
DB_USER=root
DB_PASS=
```

### 3. Start the Backend API Server
```bash
cd backend
php -S 127.0.0.1:8000
```
> 🩺 Test API healthcheck: [`http://127.0.0.1:8000/index.php`](http://127.0.0.1:8000/index.php)

### 4. Start the Frontend React Client
```bash
cd frontend
npm install
npm run dev
```
> 🚀 Open **[`http://localhost:5173`](http://localhost:5173)** in your browser!

---

## 🔑 Getting Started

Create a user account from the **Register** page, then sign in with the email and password you chose. Configure administrator credentials separately for the staff portal; do not use sample credentials in production.

---

## 🔌 REST API Documentation

All API responses follow the uniform JSON envelope:
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": { ... }
}
```

### Core API Routes

| Domain | Method | Endpoint | Description |
| :--- | :---: | :--- | :--- |
| **Auth** | `POST` | `/api/auth/login.php` | Authenticates user & issues HMAC-signed token |
| **Auth** | `POST` | `/api/auth/register.php` | Creates user account & funds ₹500 bonus |
| **Auth** | `POST` | `/api/auth/send-otp.php` | Issues secure 6-digit OTP challenge (Free Sandbox / Local) |
| **Auth** | `POST` | `/api/auth/verify-otp.php` | Validates OTP code with cooldown & attempt limits |
| **Auth** | `GET` | `/api/auth/me.php` | Fetches active session profile & balance |
| **Auth** | `POST` | `/api/auth/logout.php` | Terminates active session |
| **Wallet** | `GET` | `/api/wallet/balance.php` | Retrieves current balance (paise & rupees) |
| **Wallet** | `GET` | `/api/wallet/statement.php` | Paginated immutable double-entry ledger |
| **Wallet** | `POST` | `/api/wallet/add-money.php` | Instant wallet top-up via sandbox gateway |
| **Wallet** | `POST` | `/api/wallet/transfer.php` | Atomic P2P transfer with deadlock & fraud check |
| **Wallet** | `POST` | `/api/wallet/scratch-card.php` | Awards random cashback (₹5–₹50) via ledger |
| **Wallet** | `GET` | `/api/wallet/history.php` | Paginated transaction history |
| **Payments** | `POST` | `/api/payments/create-order.php` | Creates sandbox checkout order |
| **Payments** | `POST` | `/api/payments/verify-payment.php` | Simulates payment success, failure, or cancellation |
| **Payments** | `GET` | `/api/payments/status.php` | Order verification & status polling |
| **Notifications**| `GET` | `/api/notifications/list.php` | In-app notification center |
| **Notifications**| `POST`| `/api/notifications/read.php` | Marks notifications as read |
| **Fraud** | `POST` | `/api/fraud/check.php` | Rule-based fraud risk scoring (0-100) |
| **Recharge** | `GET` | `/api/recharge/operators.php` | Lists active telecom / DTH providers |
| **Recharge** | `GET` | `/api/recharge/plans.php?operator_id=X` | Lists tariff plans for operator |
| **Recharge** | `POST` | `/api/recharge/mobile.php` | Deducts wallet & recharges phone |
| **Recharge** | `POST` | `/api/recharge/dth.php` | Deducts wallet & recharges DTH |
| **Bills** | `GET` | `/api/bills/{type}.php?action=fetch` | Verifies consumer number & fetches bill |
| **Bills** | `POST` | `/api/bills/{type}.php` | Settles electricity, water, or gas bill |
| **Transactions** | `GET` | `/api/transactions/dashboard.php` | Recent telemetry, stats & contacts |
| **Transactions** | `GET` | `/api/transactions/list.php` | Filterable statements log |
| **Transactions** | `GET` | `/api/transactions/details.php?id=X` | Single transaction receipt details |
| **Admin** | `POST` | `/api/admin/login.php` | Administrator authentication |
| **Admin** | `GET` | `/api/admin/dashboard.php` | Platform volume & provider matrix |
| **Admin** | `GET` | `/api/admin/fraud-alerts.php` | Live fraud detection inspection stream |
| **Admin** | `GET` | `/api/admin/users.php` | User directory with status toggling |
| **Admin** | `GET` | `/api/admin/transactions.php` | System-wide transaction audit log |

---

## 🛡️ Zero Paid Services & Sandbox Strategy

PayBen operates with **100% free and open-source technologies**. No paid subscriptions or credit card registrations are ever required:

| Service Area | Free Sandbox Implementation | Production / Optional Integration |
| :--- | :--- | :--- |
| **Payment Checkout** | Built-in `MockPaymentProvider` simulating checkout, success, failure & cancellation | Optional Razorpay Test Mode with test keys |
| **OTP Verification** | `LocalOtpProvider` with bcrypt-hashed challenges, 60s cooldown, and dev transparency | Optional Brevo Free Plan for email OTP |
| **Email Service** | `MockEmailProvider` local logging + in-app notification center | Brevo Free Plan (300 emails/day) via cURL |
| **Mobile & DTH** | Local tariff catalog with Jio, Airtel, Vi, BSNL, Tata Play, Sun Direct | Local database-driven sandbox catalog |
| **BBPS Utility Bills** | Local mock BBPS provider for Electricity, Water, Piped Gas with dynamic consumer lookups | Local database-driven sandbox bills |
| **Fraud Engine** | Self-developed explainable rule-based engine (velocity, rapid duplicates, high amount) | Zero third-party or paid AI API dependencies |
| **Currency Math** | Integer paise math using `BIGINT` (`balance_paise`) to prevent floating point inaccuracies | Strict integer arithmetic |

---

## 🔒 Security Architecture

```
+-------------------------------------------------------------------+
|                       PayBen Security Model                       |
+-------------------------------------------------------------------+
| [CORS Guard]        Explicit origin whitelist (localhost:5173)     |
| [Password Hashing]  Bcrypt with dynamic salt (cost factor: 10)     |
| [SQL Injection]     100% Prepared Statements (PDO emulation OFF)  |
| [Concurrency Lock]  SELECT ... FOR UPDATE on all wallet debits    |
| [Session Defense]   HttpOnly, SameSite=Lax, ID regeneration       |
| [Double Entry]      Every debit is mirrored by a verified credit  |
+-------------------------------------------------------------------+
```

---

## 🛠️ Built With

- **[React 18](https://react.dev/)** — Modern component-based view library
- **[Vite](https://vitejs.dev/)** — Lightning-fast next-generation frontend tooling
- **[Tailwind CSS](https://tailwindcss.com/)** — Utility-first CSS framework with custom design tokens
- **[Lucide React](https://lucide.dev/)** — Beautiful & consistent iconography
- **[Axios](https://axios-http.com/)** — Promise-based HTTP client for browser & node
- **[Canvas Confetti](https://www.npmjs.com/package/canvas-confetti)** — Lightweight celebratory particle engine
- **[PHP 8.3](https://www.php.net/)** — Modern server-side scripting runtime
- **[MySQL 8.0](https://www.mysql.com/)** — Relational database management system with InnoDB transactions

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

<div align="center">
  <sub>Built with ❤️ by <a href="https://github.com/AbhijatPatel">Abhijat Patel</a> • PayBen Fintech Platform © 2026</sub>
</div>
