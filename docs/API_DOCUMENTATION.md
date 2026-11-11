# PayBen API Documentation

**Base URL:** `http://localhost/PayBen/api`  
**Response Format:** JSON  
**Auth:** PHP sessions (cookie-based). Include session cookies in all authenticated requests.

---

## Authentication

PayBen uses PHP session-based authentication. After a successful login, the server sets a session cookie (`PHPSESSID`) which must be included in all subsequent authenticated requests.

### Standard Response Format

**Success:**
```json
{ "success": true, "message": "Description", "data": { ... } }
```

**Error:**
```json
{ "success": false, "message": "Error description", "errors": { "field": "message" } }
```

---

## 1. Auth Endpoints

### POST /auth/register.php
Register a new user account.

**Request Body (JSON or form-data):**

| Field            | Type   | Required | Description                       |
|------------------|--------|----------|-----------------------------------|
| full_name        | string | Yes      | Min 3 characters                  |
| email            | string | Yes      | Valid email address               |
| mobile           | string | Yes      | 10-digit Indian mobile number     |
| password         | string | Yes      | Min 8 characters                  |
| confirm_password | string | Yes      | Must match password               |

**Example Request:**
```bash
curl -X POST http://localhost/PayBen/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"full_name":"Rahul Kumar","email":"rahul@example.com","mobile":"9876543210","password":"secret123","confirm_password":"secret123"}'
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful! You can now log in.",
  "data": { "user_id": 3, "full_name": "Rahul Kumar", "email": "rahul@example.com", "mobile": "9876543210" }
}
```

**Error Response (409 Conflict):**
```json
{ "success": false, "message": "Email or mobile number is already registered." }
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": { "mobile": "Enter a valid 10-digit Indian mobile number." }
}
```

---

### POST /auth/login.php
Authenticate a user and start a session.

**Request Body:**

| Field    | Type   | Required | Description        |
|----------|--------|----------|--------------------|
| email    | string | Yes      | Registered email   |
| password | string | Yes      | Account password   |

**Example Request:**
```bash
curl -c cookies.txt -X POST http://localhost/PayBen/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"user1@test.com","password":"password123"}'
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful. Welcome back, Rahul Sharma!",
  "data": {
    "user_id": 1,
    "full_name": "Rahul Sharma",
    "email": "user1@test.com",
    "mobile": "9876543210",
    "wallet_balance": 5000.00,
    "wallet_balance_fmt": "₹5,000.00"
  }
}
```

**Error Response (401):**
```json
{ "success": false, "message": "Invalid email or password." }
```

---

### POST /auth/logout.php
Destroy the current session.

```bash
curl -b cookies.txt -X POST http://localhost/PayBen/api/auth/logout.php
```

**Success Response (200):**
```json
{ "success": true, "message": "Logged out successfully." }
```

---

## 2. Wallet Endpoints

### GET /wallet/balance.php
Get the current user's wallet balance. *(Requires login)*

```bash
curl -b cookies.txt http://localhost/PayBen/api/wallet/balance.php
```

**Success Response (200):**
```json
{
  "success": true,
  "data": { "balance": 4751.00, "balance_formatted": "₹4,751.00", "user_id": 1 }
}
```

---

### POST /wallet/add-money.php
Add money to the wallet (simulated top-up). *(Requires login)*

**Request Body:**

| Field  | Type   | Required | Description              |
|--------|--------|----------|--------------------------|
| amount | number | Yes      | Amount (₹100 to ₹50,000)|

```bash
curl -b cookies.txt -X POST http://localhost/PayBen/api/wallet/add-money.php \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000}'
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "₹1,000.00 added to your wallet successfully! [SIMULATED TOP-UP]",
  "data": {
    "transaction_id": "WT20261110A3F2B1",
    "amount": 1000,
    "amount_formatted": "₹1,000.00",
    "new_balance": 5751.00,
    "new_balance_fmt": "₹5,751.00",
    "simulated": true
  }
}
```

**Error Response (402):**
```json
{ "success": false, "message": "Amount must be between ₹100 and ₹50,000." }
```

---

### GET /wallet/history.php
Get wallet transaction history. *(Requires login)*

| Param | Type | Required | Description          |
|-------|------|----------|----------------------|
| page  | int  | No       | Page number (default 1, 20 per page) |

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 5,
        "type": "credit",
        "amount": "1000.00",
        "description": "Wallet top-up (SIMULATED TOP-UP)",
        "reference_id": "WT20261110A3F2B1",
        "balance_after": "5751.00",
        "created_at": "2026-11-10 15:00:00"
      }
    ],
    "pagination": { "current_page": 1, "total_pages": 1, "total_records": 5 }
  }
}
```

---

## 3. Recharge Endpoints

### GET /recharge/plans.php
Get available recharge plans for an operator.

| Param       | Type | Required | Description   |
|-------------|------|----------|---------------|
| operator_id | int  | Yes      | Operator ID   |

```bash
curl "http://localhost/PayBen/api/recharge/plans.php?operator_id=1"
```

**Success Response (200):**
```json
{
  "success": true,
  "operator": { "id": 1, "name": "Reliance Jio", "type": "mobile" },
  "count": 4,
  "plans": [
    {
      "id": 1, "plan_name": "Jio Cricket Special", "amount": "149.00",
      "validity": "20 Days", "data_limit": "1 GB/Day",
      "description": "Unlimited True 5G Voice Calls...", "plan_type": "prepaid"
    }
  ]
}
```

---

### POST /recharge/mobile.php
Perform a mobile prepaid recharge. *(Requires login)*

| Field         | Type   | Required | Description                  |
|---------------|--------|----------|------------------------------|
| mobile_number | string | Yes      | 10-digit mobile number       |
| operator_id   | int    | Yes      | Mobile operator ID (1-4)     |
| amount        | number | Yes      | Recharge amount (₹10-₹999)   |
| plan_id       | int    | No       | Optional plan ID             |

```bash
curl -b cookies.txt -X POST http://localhost/PayBen/api/recharge/mobile.php \
  -H "Content-Type: application/json" \
  -d '{"mobile_number":"9876543210","operator_id":1,"amount":249,"plan_id":2}'
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Mobile recharge of ₹249.00 successful! [SIMULATED TRANSACTION]",
  "data": {
    "transaction_id": "MR20261110B4C1D2",
    "mobile_number": "9876543210",
    "operator": "Reliance Jio",
    "amount": 249,
    "amount_formatted": "₹249.00",
    "new_balance": 4502.00,
    "new_balance_fmt": "₹4,502.00",
    "status": "SUCCESS",
    "simulated": true,
    "timestamp": "2026-11-10 15:30:00"
  }
}
```

**Insufficient Balance (402):**
```json
{
  "success": false,
  "message": "Insufficient wallet balance.",
  "available_balance": "₹49.00",
  "required_amount": "₹249.00"
}
```

---

### POST /recharge/dth.php
Perform a DTH subscription recharge. *(Requires login)*

| Field       | Type   | Required | Description                  |
|-------------|--------|----------|------------------------------|
| customer_id | string | Yes      | Subscriber ID (8–15 digits)  |
| operator_id | int    | Yes      | DTH operator ID (5-8)        |
| amount      | number | Yes      | Recharge amount (₹100-₹10,000) |

```bash
curl -b cookies.txt -X POST http://localhost/PayBen/api/recharge/dth.php \
  -H "Content-Type: application/json" \
  -d '{"customer_id":"1002345678","operator_id":5,"amount":499}'
```

**Success Response:** Same structure as mobile recharge with `transaction_id` prefix `DT`.

---

## 4. Bill Endpoints

Each utility type (electricity, water, gas) has the same interface:

### GET /bills/electricity.php?action=fetch
Fetch a pending bill. *(Requires login)*

| Param           | Type   | Required | Description        |
|-----------------|--------|----------|--------------------|
| action          | string | Yes      | Must be `fetch`    |
| provider_id     | int    | Yes      | Provider ID        |
| consumer_number | string | Yes      | Consumer account # |

```bash
curl -b cookies.txt "http://localhost/PayBen/api/bills/electricity.php?action=fetch&provider_id=3&consumer_number=3004567890"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Bill details fetched successfully.",
  "data": {
    "bill_id": 1,
    "consumer_name": "Rahul Sharma",
    "consumer_number": "3004567890",
    "provider": "Bangalore Electricity Supply Company (BESCOM)",
    "state": "Karnataka",
    "bill_amount": "1850.50",
    "amount_formatted": "₹1,850.50",
    "bill_date": "2026-11-05",
    "due_date": "2026-11-25",
    "status": "unpaid"
  }
}
```

### POST /bills/electricity.php
Pay a fetched bill. *(Requires login)*

```json
{ "action": "pay", "bill_id": 1 }
```

Success response includes `transaction_id` with prefix `EB`, updated balance, and simulated flag.

> Same interface applies for `/bills/water.php` (prefix `WB`) and `/bills/gas.php` (prefix `GB`).

---

## 5. Transaction Endpoints

### GET /transactions/list.php
Get paginated transaction history. *(Requires login)*

| Param     | Type   | Required | Description                                |
|-----------|--------|----------|--------------------------------------------|
| type      | string | No       | mobile_recharge, dth_recharge, electricity, water, gas, wallet_topup |
| status    | string | No       | SUCCESS, PENDING, FAILED                   |
| search    | string | No       | Search by transaction ID, number, provider |
| date_from | date   | No       | YYYY-MM-DD                                 |
| date_to   | date   | No       | YYYY-MM-DD                                 |
| page      | int    | No       | Page number (default 1)                    |

```bash
curl -b cookies.txt "http://localhost/PayBen/api/transactions/list.php?type=mobile_recharge&status=SUCCESS&page=1"
```

### GET /transactions/details.php
Get single transaction details. *(Requires login)*

```bash
curl -b cookies.txt "http://localhost/PayBen/api/transactions/details.php?transaction_id=MR20261110B4C1D2"
```

---

## 6. Admin Endpoints

> All admin endpoints require an **admin session** (login via `/admin/login.php` first).

### GET /admin/dashboard.php — Platform statistics
### GET /admin/users.php — List all users (filterable)
### POST /admin/users.php — Toggle user status (`action`: enable/disable)
### GET /admin/transactions.php — All platform transactions (filterable)

---

## 7. MIT App Inventor Integration

### Setup
1. In App Inventor, add a **Web** component to your screen.
2. Set `Web.Url` to the full API endpoint URL.
3. For POST requests, set `Web.RequestHeaders` to include `Content-Type: application/json`.
4. The Web component automatically handles session cookies after login.

### Login Flow (Blocks)
```
When Button_Login.Click
  Set Web.Url to "http://YOUR_IP/PayBen/api/auth/login.php"
  Set Web.PostText to JsonTextEncode({"email": TextBox_Email.Text, "password": TextBox_Pass.Text})
  Call Web.PostText

When Web.GotText
  Set response to JsonTextDecode(Web.ResponseContent)
  If response["success"] = true
    Set Label_Balance.Text to response["data"]["wallet_balance_fmt"]
    Open Screen "Dashboard"
  Else
    Set Label_Error.Text to response["message"]
```

### Get Wallet Balance
```
When Screen.Initialize
  Set Web.Url to "http://YOUR_IP/PayBen/api/wallet/balance.php"
  Call Web.Get

When Web.GotText
  Set response to JsonTextDecode(Web.ResponseContent)
  Set Label_Balance.Text to response["data"]["balance_formatted"]
```

### Mobile Recharge
```
When Button_Recharge.Click
  Set Web.Url to "http://YOUR_IP/PayBen/api/recharge/mobile.php"
  Set body to CreateObject
  Call Object.Set(body, "mobile_number", TextBox_Mobile.Text)
  Call Object.Set(body, "operator_id", Spinner_Operator.SelectionIndex)
  Call Object.Set(body, "amount", TextBox_Amount.Text)
  Set Web.PostText to JsonTextEncode(body)
  Call Web.PostText

When Web.GotText
  Set response to JsonTextDecode(Web.ResponseContent)
  If response["success"] = true
    Show Notifier "Recharge Successful! TXN: " + response["data"]["transaction_id"]
  Else
    Show Notifier response["message"]
```

### Key MIT App Inventor Components Needed
| Component   | Purpose                          |
|-------------|----------------------------------|
| Web         | HTTP GET/POST requests           |
| Notifier    | Show alerts / toast messages     |
| Label       | Display balance, transaction data|
| TextBox     | User input fields                |
| ListPicker  | Select operator / plan           |
| TinyDB      | Cache user session data locally  |

> **Important:** Replace `YOUR_IP` with your computer's local IP address (e.g., `192.168.1.5`) when testing on a physical Android device on the same Wi-Fi network.

