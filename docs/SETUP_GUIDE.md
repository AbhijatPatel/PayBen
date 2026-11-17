# PayBen Setup Guide

## System Requirements

| Component | Minimum Version | Recommended       |
|-----------|-----------------|-------------------|
| OS        | Windows 10      | Windows 10/11     |
| XAMPP     | 8.0.x           | 8.2.x (PHP 8.2+)  |
| PHP       | 8.0             | 8.2               |
| MySQL     | 5.7             | 8.0               |
| Browser   | Chrome 90+      | Latest Chrome/Edge|
| RAM       | 2 GB            | 4 GB              |
| Disk      | 100 MB          | 500 MB            |

---

## Step 1 — Install XAMPP

1. Download XAMPP from: https://www.apachefriends.org/download.html
   - Choose the version with **PHP 8.0 or higher**
2. Run the installer with default settings
3. Install to `C:\xampp\` (default)
4. At the end, launch the **XAMPP Control Panel**

---

## Step 2 — Start Apache & MySQL

1. Open **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`)
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both should show green status indicators
5. Verify by opening: `http://localhost` → You should see the XAMPP dashboard

**If ports are in use:**
- Apache uses port 80 (and 443). If Skype or IIS is blocking: in XAMPP → Apache → Config → `httpd.conf` → change `Listen 80` to `Listen 8080`
- MySQL uses port 3306. If another MySQL is running, stop it first.

---

## Step 3 — Copy PayBen Files

Copy the entire `PayBen/` folder to the XAMPP web root:

```
Source:      PayBen\  (wherever you downloaded/extracted it)
Destination: C:\xampp\htdocs\PayBen\
```

After copying, the structure should be:
```
C:\xampp\htdocs\PayBen\
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── config\
├── includes\
├── pages\
├── admin\
├── api\
├── assets\
├── database\
└── docs\
```

---

## Step 4 — Import the Database

### Via phpMyAdmin (Recommended)

1. Open: `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Enter database name: `payben_db`
4. Click **Create**
5. Click on `payben_db` in the left sidebar
6. Click the **Import** tab at the top
7. Click **Choose File**
8. Browse to: `C:\xampp\htdocs\PayBen\database\payben_db.sql`
9. Click **Go** (bottom of page)

You should see: **"Import has been successfully finished."**

### Verify Import

Click on `payben_db` in phpMyAdmin. You should see **9 tables:**
- admins
- operators
- recharge_plans
- sample_bills
- transactions
- users
- utility_providers
- wallet_transactions
- wallets

---

## Step 5 — Configure Database Connection

If your MySQL credentials differ from default, edit `config/database.php`:

```php
define('DB_HOST', 'localhost');   // Usually 'localhost'
define('DB_NAME', 'payben_db');   // Database name
define('DB_USER', 'root');        // MySQL username (default: root)
define('DB_PASS', '');            // MySQL password (default: empty in XAMPP)
```

> **XAMPP default credentials:** username=`root`, password=`(empty)`

---

## Step 6 — Access the Application

Open your browser and visit:

```
http://localhost/PayBen
```

You should see the PayBen landing page with "Get Started" and "Login" buttons.

---

## Step 7 — Google Authentication API (Optional)

The React login and registration pages use email and password only; they do not show Google sign-in controls. The backend `/api/auth/google.php` endpoint remains available for existing API clients and verifies Google ID tokens server-side. Those API clients need a Google Web OAuth client ID configured as `GOOGLE_CLIENT_ID` in the backend; the frontend does not need `VITE_GOOGLE_CLIENT_ID`. No client secret or redirect URI is used by the ID-token flow.

On Windows PHP installs that cannot verify Google's certificate chain, download the PEM CA bundle from `https://curl.se/ca/cacert.pem`, save it as `backend/certs/cacert.pem`, and set `GOOGLE_CA_BUNDLE=C:\PayBen\backend\certs\cacert.pem` in the local backend environment. TLS peer and hostname verification remain enabled. Restart PHP after changing backend environment variables.

## Local Development (Windows PowerShell)

Start MySQL first and set the existing database password in `backend/.env` as `DB_PASS=...`. The checked-in local configuration currently has no database password, while MySQL requires one; do not reset or recreate the database to work around this.

In the first PowerShell terminal:

```powershell
Set-Location C:\PayBen\backend
php -S 127.0.0.1:8000 router.php
```

In a second PowerShell terminal:

```powershell
Set-Location C:\PayBen\frontend
npm run dev -- --host 127.0.0.1
```

Open `http://127.0.0.1:5173/login`. Vite proxies `/api` requests to `http://127.0.0.1:8000`; the configured `VITE_API_BASE_URL=/api` keeps the backend URL out of page components. Set `FRONTEND_URL` to the frontend's exact origin on deployment. The backend now permits only configured/local origins rather than every Vercel subdomain.

For development password recovery, leave `APP_ENV=development` and `EMAIL_PROVIDER=mock`; the page displays a clearly labelled, single-use reset link for an existing active account. Production password recovery requires `APP_ENV=production`, `EMAIL_PROVIDER=brevo`, and a configured `BREVO_API_KEY`; reset tokens are never returned by the production API. Use a unique random `APP_SECRET` in production (generate one with `php -r "echo bin2hex(random_bytes(32));"`), and never commit `.env` files.

## Step 8 — Test Login

**User Login:**
- URL: `http://127.0.0.1:5173/login`
- Register a new account, then sign in using the email and password you chose.

**Admin Login:**
- URL: `http://localhost/PayBen/admin/login.php`
- Email: `admin@payben.com`
- Password: `admin123`

---

## Testing Checklist

After setup, verify the following:

### Authentication
- [ ] Landing page loads correctly with animations
- [ ] Register page — register a new user
- [ ] Sign in with a registered account → redirects to dashboard
- [ ] Invalid login shows error message
- [ ] Logout clears session and redirects to login

### Dashboard
- [ ] Wallet balance displays ₹5,000.00 for test users
- [ ] All 4 stat cards show correct numbers
- [ ] All 6 quick action buttons navigate correctly
- [ ] Recent transactions table shows sample data

### Mobile Recharge
- [ ] Operator dropdown loads from database
- [ ] Select Jio → Plans load dynamically
- [ ] Select ₹249 plan → Amount fills, summary shows
- [ ] Click "Proceed to Recharge" → Confirmation dialog
- [ ] Transaction succeeds → Receipt shows with Transaction ID
- [ ] Wallet balance updates (reduces by ₹249)

### Bill Payment
- [ ] Electricity: Select BESCOM → Consumer No: `3004567890` → Fetch Bill
- [ ] Bill details display (₹1,850.50 for Rahul Sharma)
- [ ] Pay Bill → Success receipt

> ⚠️ Consumer no. `3004567890` is already marked PAID in sample data. 
> Use `1001234567` (Tata Power) or `2008765432` (BSES) for unpaid tests.

### Wallet
- [ ] Add ₹1,000 → Success message → Balance updates
- [ ] Try to add ₹5 (below minimum) → Validation error
- [ ] Wallet transaction history shows credit entries

### Transactions
- [ ] All transactions visible in table
- [ ] Filter by type (Mobile Recharge)
- [ ] Filter by status (SUCCESS)
- [ ] Search by Transaction ID
- [ ] "View" button shows transaction detail modal

### Admin
- [ ] Admin login with `admin@payben.com / admin123`
- [ ] Dashboard shows real-time stats
- [ ] Users table lists registered users
- [ ] Disable user1 → user1 cannot login → Re-enable
- [ ] Manage plans → Add a new operator → Add a plan

---

## Troubleshooting

### "Could not connect to MySQL"
- Verify MySQL is running in XAMPP Control Panel
- Check `config/database.php` credentials
- Ensure `payben_db` database exists in phpMyAdmin

### "Class not found" / PHP errors
- Ensure PHP version is 8.0+: check at `http://localhost/phpmyadmin` → server info
- Check that all files were copied completely

### CSS / JS not loading
- Verify `APP_URL` in `config/config.php` matches your server URL
- Default is `http://localhost/PayBen` — change if using a different port
- If using port 8080: `http://localhost:8080/PayBen`

### Blank page (no output)
- Enable PHP error display: in `config/config.php` set `ini_set('display_errors', 1)`
- Check `C:\xampp\apache\logs\error.log` for PHP/Apache errors

### Session not working (keeps logging out)
- Ensure `session.save_path` is writable: `C:\xampp\tmp`
- In XAMPP Control Panel → PHP → `php.ini` → find `session.save_path` and ensure it exists

### Database import fails
- Ensure the SQL file is not corrupted (should be ~24KB)
- Try importing with MySQL command line: 
  ```
  C:\xampp\mysql\bin\mysql.exe -u root payben_db < "C:\xampp\htdocs\PayBen\database\payben_db.sql"
  ```

---

## Configuration for Production

> ⚠️ PayBen is a demo application. If deploying to a real server, apply these changes:

1. **Turn off error display** in `config/config.php`:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **Use strong database credentials** (not root/empty)

3. **Enable HTTPS** and update `APP_URL` to `https://yourdomain.com`

4. **Set secure session flags** in `config/config.php`:
   ```php
   ini_set('session.cookie_secure', 1);  // Requires HTTPS
   ```

5. **Hide `config/` and `database/` from web access** using `.htaccess`:
   ```apache
   <Files "*.sql">
     Order Deny,Allow
     Deny from all
   </Files>
   ```

6. **Change default admin password** immediately after first login

7. **Remove demo credentials** from login pages before going live

---

## Git Setup (Optional)

Initialize a Git repository for version control:

```bash
cd C:\xampp\htdocs\PayBen
git init
git add .
git commit -m "feat: initial PayBen project setup"
```

Create a `.gitignore` file:
```
config/database.php
*.log
.DS_Store
Thumbs.db
```

Push to GitHub:
```bash
git remote add origin https://github.com/yourusername/payben.git
git branch -M main
git push -u origin main
```

---

## Support

- **Issues:** Open a GitHub issue with error messages and screenshots
- **Email:** support@payben.com *(demo contact)*

