# POS System — Sari-Sari Store Manager

A full-stack Point of Sale system built with pure PHP + MySQL (PDO) + XAMPP + Pusher WebSocket.

---

## Tech Stack

- **Backend:** Pure PHP (no frameworks), PDO
- **Database:** MySQL via XAMPP
- **Real-time:** Pusher WebSocket SDK
- **Security:** Bcrypt, CSRF tokens, Prepared Statements, XSS sanitization

---

## Setup Instructions

### 1. Add to hosts file

```
127.0.0.1   casestudy
```

- **Windows:** `C:\Windows\System32\drivers\etc\hosts`
- **Linux/Mac:** `/etc/hosts`

### 2. Place project in XAMPP

Copy the `pos-system` folder to:
```
C:\xampp\htdocs\pos-system\     (Windows)
/opt/lampp/htdocs/pos-system/   (Linux)
```

### 3. Create .env file

Copy `.env.example` to `.env` and fill in your values:

```
DB_HOST=casestudy
DB_NAME=pos_db
DB_USER=root
DB_PASS=
DB_PORT=3306

PUSHER_APP_ID=your_app_id
PUSHER_KEY=your_key
PUSHER_SECRET=your_secret
PUSHER_CLUSTER=ap1
```

> Get free Pusher credentials at: https://pusher.com

### 4. Import database

In phpMyAdmin or MySQL CLI:

```sql
source /path/to/pos-system/sql/schema.sql
source /path/to/pos-system/sql/procedures.sql
source /path/to/pos-system/sql/triggers.sql
```

### 5. Access the system

```
http://casestudy/pos-system/
```

**Default login:**
- Username: `admin`
- Password: `password`

> Change the default password immediately after first login.

---

## Features

- Sales transaction (cart, payment, change, receipt)
- Product management (CRUD)
- Inventory tracking (auto stock deduction via trigger)
- Ad Hoc reporting (custom date/product/category filtering)
- Real-time stock updates (Pusher WebSocket)
- Role-based access (Admin / Cashier)
- Dashboard with charts and low stock alerts

---

## Security

- Passwords hashed with bcrypt
- CSRF tokens on all POST requests
- All queries use PDO prepared statements
- All output sanitized with `htmlspecialchars`
- `.env` excluded from repository

---

## Database

- Stored Procedure: `process_sale`, `stock_in`
- Triggers: `after_sale_insert` (auto stock deduction), `after_sale_item_delete`

---

## GitHub Notes

- Do NOT push `.env` (it's in `.gitignore`)
- Push `.env.example` to show key structure
- SQL files are included in `/sql/`
