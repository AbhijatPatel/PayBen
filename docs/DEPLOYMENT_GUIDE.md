# PayBen Deployment Guide (Vercel + Railway)

This guide walks you through deploying PayBen into production with zero cost using:
- **Frontend (React SPA)**: [Vercel](https://vercel.com/)
- **Backend (PHP REST API)**: [Railway](https://railway.app/)
- **Database (MySQL 8.0)**: [Railway MySQL](https://railway.app/)

---

## Part 1: Deploy Backend & MySQL on Railway

### 1. Create Railway Project & MySQL Database
1. Go to [railway.app](https://railway.app/) and sign in with your GitHub account.
2. Click **+ New Project** and select **Provision MySQL**.
3. Railway will provision a MySQL database container in seconds.
4. Click on the **MySQL** card, navigate to the **Variables** tab, and copy the `MYSQL_URL` (or `DATABASE_URL`).

### 2. Import Database Schema
1. Click the **Data** tab on your Railway MySQL card (or connect using DBeaver / TablePlus / MySQL Workbench using the connection credentials from the **Connect** tab).
2. Open [`backend/database/payben_db.sql`](../backend/database/payben_db.sql) from your repo and execute the script.
3. This creates all 9 tables (`users`, `wallets`, `transactions`, `operators`...) and seeds test accounts (`user1@test.com`, `user2@test.com`, `admin@payben.com`).

### 3. Deploy PHP Backend
1. In the same Railway project, click **+ New** (top right) $\rightarrow$ **GitHub Repo**.
2. Select your repository: **`AbhijatPatel/PayBen`**.
3. Once created, click on the new service card and go to **Settings**:
   - Under **Root Directory**, set it to: `backend`
   - Under **Build Command**, leave empty (Railway detects the `Dockerfile` in `backend/`).
4. Go to the **Variables** tab and click **New Variable**:
   - `DATABASE_URL` = (Click **Add Reference** and select `${{MySQL.MYSQL_URL}}` — Railway links them automatically!)
   - `FRONTEND_URL` = `https://your-payben-app.vercel.app` (you can update this after deploying on Vercel)
5. Go to **Settings** $\rightarrow$ **Networking** $\rightarrow$ click **Generate Domain**.
6. Copy your public backend URL (e.g., `https://payben-backend-production.up.railway.app`).
7. Verify by opening in browser: `https://your-backend-url/index.php`. It should return:
   ```json
   { "status": "online", "service": "PayBen REST API Engine", ... }
   ```

---

## Part 2: Deploy Frontend on Vercel

### 1. Import Project to Vercel
1. Go to [vercel.com](https://vercel.com/) and sign in with GitHub.
2. Click **Add New...** $\rightarrow$ **Project**.
3. Find and import **`AbhijatPatel/PayBen`**.

### 2. Configure Build & Root Directory
1. On the Configure Project screen:
   - Next to **Root Directory**, click **Edit** and select: `frontend`
   - Framework Preset: **Vite** (auto-detected)
   - Build Command: `npm run build` (auto-detected)
   - Output Directory: `dist` (auto-detected)
2. Expand the **Environment Variables** section and add:
   - **Name:** `VITE_API_BASE_URL`
   - **Value:** `https://your-railway-backend-url.up.railway.app/api`
3. Click **Deploy**!

Vercel will build and deploy your React application in ~45 seconds and give you a live URL like `https://payben.vercel.app`.

---

## Part 3: Final CORS Handshake

1. Return to your **Railway** backend service.
2. Go to **Variables** and ensure `FRONTEND_URL` is set to your live Vercel URL (e.g. `https://payben.vercel.app`).
3. PayBen's built-in CORS middleware also automatically accepts all `*.vercel.app` domains.

---

## Part 4: Alternative All-in-One Deployment (Docker / VPS)

If deploying to a self-hosted Ubuntu VPS (DigitalOcean / AWS / Hetzner):
1. Install Docker & Docker Compose on your server.
2. Clone the repo:
   ```bash
   git clone https://github.com/AbhijatPatel/PayBen.git
   cd PayBen
   ```
3. Run with Docker Compose or Nginx proxying port 8000 for backend and serving `frontend/dist` for frontend.

