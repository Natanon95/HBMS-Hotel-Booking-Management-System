# HotelPMS — Hotel Booking System

A clean, full-featured Hotel Property Management System built with **PHP 8+ / MySQL / Vanilla JS** — no frameworks.

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

## Features

- **Role-based access** — Admin & Receptionist roles
- **Booking state machine** — pending → confirmed → checked-in → checked-out / cancelled
- **Double-booking prevention** — date-range conflict check on every booking
- **Dashboard** — stat cards, revenue chart (Chart.js), today's arrivals & departures
- **Room grid** — visual overview with availability colours
- **Guest management** — full CRUD with booking history
- **Payments** — record cash, card, bank transfer; balance tracking
- **Extras** — add meals, tours, services to bookings
- **Housekeeping** — daily task board with status workflow
- **Reports** — date-range revenue report, source breakdown, room-type analysis
- **CSV Export** — bookings, guests, payments, reports
- **Demo mode** — seed data ready to explore

---

## Installation (XAMPP)

### Requirements
- XAMPP (Apache + MySQL) or any PHP 8.2+ / MySQL 8.0+ stack
- No Composer required — zero dependencies

### Step-by-step

**1. Clone the repository**
```bash
git clone https://github.com/Natanon95/HBMS-Hotel-Booking-Management-System.git
cd HBMS-Hotel-Booking-Management-System
```

**2. Copy to XAMPP web root**
```
C:\xampp\htdocs\hotel-booking\
```

**3. Create the database config file**
```bash
cp config/database.example.php config/database.php
```
> Edit `config/database.php` if your MySQL username/password differs from the default (`root` / empty).

**4. Create the database and import SQL**

Open **phpMyAdmin** → `http://localhost/phpmyadmin`

- Click **New** → create database named `hotel_booking`
- Select `hotel_booking` → click **Import**
- Import `sql/schema.sql` first, then `sql/seed.sql`

**5. Open in browser**
```
http://localhost/hotel-booking
```

### Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@hotel.demo | password |
| Receptionist | sara@hotel.demo | password |

> If login fails after importing the seed, visit `http://localhost/hotel-booking/setup.php` and click **"Fix Passwords Only"** — this regenerates bcrypt hashes for your local PHP version.

---

## Project Structure

```
hotel-booking/
├── config/
│   ├── database.example.php   ← copy this to database.php
│   └── database.php           ← your local config (gitignored)
├── core/
│   ├── Auth.php               ← session auth & role guards
│   ├── Database.php           ← PDO singleton + query helpers
│   ├── helpers.php            ← e(), formatMoney(), CSRF, flash, etc.
│   └── Mailer.php             ← email wrapper (logs in DEMO_MODE)
├── modules/
│   ├── dashboard/             ← stats, charts, arrivals
│   ├── bookings/              ← CRUD + check-in/out/cancel
│   ├── rooms/                 ← room grid + status management
│   ├── guests/                ← guest profiles + history
│   ├── payments/              ← payment records + monthly view
│   ├── reports/               ← date-range revenue reports
│   ├── housekeeping/          ← daily task board
│   └── settings/              ← user management (admin only)
├── api/                       ← POST endpoints for JS forms
├── assets/
│   ├── css/app.css            ← custom design system (no framework)
│   └── js/app.js              ← vanilla JS helpers
├── includes/
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── sql/
│   ├── schema.sql             ← all tables + indexes
│   └── seed.sql               ← demo data (rooms, guests, bookings)
├── login.php
├── logout.php
└── index.php                  ← redirects to dashboard or login
```

## Database Tables

| Table | Purpose |
|-------|---------|
| `users` | Staff accounts (admin / receptionist) |
| `guests` | Guest profiles with ID/passport info |
| `room_types` | Room categories with base pricing & amenities |
| `rooms` | Individual rooms with live status |
| `bookings` | Reservations — full status state machine |
| `payments` | Payment records per booking |
| `booking_extras` | Add-on services (meals, tours, etc.) |
| `housekeeping` | Cleaning & maintenance task board |

## Security

- CSRF tokens on all POST forms
- PDO prepared statements throughout — no SQL injection
- `password_hash(PASSWORD_BCRYPT)` for all passwords
- Role enforcement via `Auth::requireRole()`
- `htmlspecialchars()` on all output via `e()`
- Exception details logged server-side only — never shown to users
- `config/database.php` is gitignored — credentials never reach version control

**Before going to production:**
- Set `DEMO_MODE = false` in `config/database.php`
- Set a strong MySQL password
- Delete `setup.php`
- Enable HTTPS

## Tech Stack

- PHP 8.2+
- MySQL 8.0 / MariaDB 10.6+
- Vanilla JS ES6+
- [Chart.js 4](https://www.chartjs.org/) (CDN)
- No CSS frameworks — fully custom design system

## License

MIT — free to use, modify, and distribute.
