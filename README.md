# PayBen

A full-stack UPI payment, mobile recharge, and bill payment web app built with PHP and MySQL. Features a digital wallet, P2P transfers, scratch card rewards, and an admin panel.

> **Note**: All payments are simulated — no real money is involved. Built as a learning/portfolio project.

![PHP](https://img.shields.io/badge/PHP-8.0+-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?logo=bootstrap&logoColor=white)

---

## What it does

- **Mobile & DTH Recharge** — Browse plans by operator, select amount, pay from wallet
- **Bill Payments** — Electricity, water, gas — fetch bill by consumer number, pay instantly
- **Digital Wallet** — Add money, check balance, view transaction history
- **UPI Transfers** — Send money to other users by mobile number or email
- **Scratch Cards** — Random cashback rewards (₹5–₹50) after transactions
- **Admin Dashboard** — Manage users, view all transactions, CRUD operators & plans

## Tech Stack

| Layer | Tech |
|-------|------|
| Frontend | HTML5, CSS3, Bootstrap 5.3, Vanilla JS |
| Backend | PHP 8.0+ (PDO, sessions) |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Server | Apache (XAMPP) or PHP built-in server |

## Quick Start

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) with PHP 8.0+ and MySQL — or standalone PHP + MySQL
- A modern browser

### Setup

1. **Clone & place in your web root**

   ```bash
   git clone https://github.com/your-username/PayBen.git
   # If using XAMPP, clone into C:\xampp\htdocs\PayBen
   ```

2. **Create the database**

   Open phpMyAdmin (or any MySQL client) and import `database/payben_db.sql`:

   ```bash
   mysql -u root -p < database/payben_db.sql
   ```

3. **Configure database connection**

   Edit `config/database.php` if your MySQL credentials differ from the defaults:

   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', 3306);
   define('DB_NAME', 'payben_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Start the server**

   **Option A** — XAMPP: Start Apache + MySQL from the XAMPP control panel, then open `http://localhost/PayBen`

   **Option B** — PHP built-in server:
   ```bash
   cd PayBen
   php -S localhost:8000
   # Open http://localhost:8000
   ```

5. **Login with test accounts**

   | Role | Email | Password |
   |------|-------|----------|
   | User | `user1@test.com` | `password123` |
   | User | `user2@test.com` | `password123` |
   | Admin | `admin@payben.com` | `admin123` |

   Both test users start with ₹5,000 in their wallet.

## Project Structure

```
PayBen/
├── config/
│   ├── config.php          # App constants, CSRF, helpers
│   └── database.php        # PDO connection (singleton)
├── includes/
│   ├── auth.php            # Login/session checks
│   ├── header.php          # HTML <head>, CSS imports
│   ├── footer.php          # JS imports, toast notifications
│   ├── navbar.php          # Top navigation bar
│   └── sidebar.php         # Dashboard sidebar menu
├── pages/
│   ├── dashboard.php       # Main user dashboard
│   ├── wallet.php          # Wallet balance & history
│   ├── mobile-recharge.php # Mobile recharge flow
│   ├── dth-recharge.php    # DTH recharge flow
│   ├── electricity.php     # Electricity bill payment
│   ├── water.php           # Water bill payment
│   ├── gas.php             # Gas bill payment
│   ├── transactions.php    # Transaction history
│   └── profile.php         # User profile
├── admin/
│   ├── dashboard.php       # Admin overview & stats
│   ├── users.php           # User management
│   ├── transactions.php    # All transactions
│   ├── manage-plans.php    # Recharge plans CRUD
│   ├── manage-bills.php    # Utility bills management
│   └── includes/           # Admin header, sidebar, footer
├── api/
│   ├── auth/               # login, register, logout
│   ├── wallet/             # balance, add-money, transfer, history, scratch-card
│   ├── recharge/           # mobile, dth, plans
│   ├── bills/              # electricity, water, gas
│   ├── transactions/       # list, details
│   └── admin/              # dashboard stats, users, transactions
├── assets/
│   ├── css/style.css       # All styles
│   └── js/app.js           # Client-side logic
├── database/
│   └── payben_db.sql       # Schema + seed data
├── docs/
│   ├── API_DOCUMENTATION.md
│   ├── DATABASE_DESIGN.md
│   └── SETUP_GUIDE.md
├── login.php               # User login page
├── register.php            # User registration
├── index.php               # Landing page
└── dashboard.php           # Dashboard (authenticated)
```

## API Endpoints

All API responses follow `{ "success": bool, "message": string, "data": {} }`.

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register.php` | Create account |
| POST | `/api/auth/login.php` | Login (returns session cookie) |
| POST | `/api/auth/logout.php` | Logout |

### Wallet
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/wallet/balance.php` | Get wallet balance |
| POST | `/api/wallet/add-money.php` | Add funds to wallet |
| POST | `/api/wallet/transfer.php` | Send money to another user |
| GET | `/api/wallet/history.php` | Wallet transaction log |
| POST | `/api/wallet/scratch-card.php` | Claim scratch card reward |

### Recharge & Bills
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/recharge/plans.php` | List plans by operator |
| POST | `/api/recharge/mobile.php` | Process mobile recharge |
| POST | `/api/recharge/dth.php` | Process DTH recharge |
| GET | `/api/bills/electricity.php` | Fetch/pay electricity bill |
| GET | `/api/bills/water.php` | Fetch/pay water bill |
| GET | `/api/bills/gas.php` | Fetch/pay gas bill |

Full API docs with request/response examples: [docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md)

## Database

9 tables — `users`, `admins`, `wallets`, `wallet_transactions`, `operators`, `recharge_plans`, `utility_providers`, `sample_bills`, `transactions`.

All money columns use `DECIMAL(12,2)`. Wallet operations use `SELECT ... FOR UPDATE` inside transactions to prevent race conditions.

Schema details: [docs/DATABASE_DESIGN.md](docs/DATABASE_DESIGN.md)

## Security

- Passwords hashed with `password_hash()` (bcrypt)
- Prepared statements everywhere (no raw SQL interpolation)
- CSRF tokens on all forms
- Session regeneration on login
- XSS protection via `htmlspecialchars()` output encoding
- HTTP-only, SameSite=Strict session cookies

## Screenshots

After logging in, you'll see the dashboard with quick actions for recharge, bill pay, and wallet operations. The admin panel is at `/admin/login.php`.

## Contributing

1. Fork it
2. Create a branch (`git checkout -b feature/xyz`)
3. Commit (`git commit -m 'Add xyz'`)
4. Push (`git push origin feature/xyz`)
5. Open a PR

## License

MIT — see [LICENSE](LICENSE) for details.
