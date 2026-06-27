# HotelPMS — Hotel Booking System

A clean, full-featured Hotel Property Management System built with **PHP 8+ / MySQL / Vanilla JS** — no frameworks.

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

## Quick Start (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\hotel-booking`
2. Start Apache + MySQL in XAMPP Control Panel
3. Open **phpMyAdmin** → create database `hotel_booking`
4. Import `sql/schema.sql` then `sql/seed.sql`
5. Visit **http://localhost/hotel-booking**

### Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@hotel.demo | password |
| Receptionist | sara@hotel.demo | password |

## Project Structure

```
hotel-booking/
├── config/         database.php — PDO connection & constants
├── core/           Auth.php, Database.php, helpers.php, Mailer.php
├── modules/        dashboard, bookings, rooms, guests, payments, reports, housekeeping, settings
├── api/            JSON/POST endpoints (payments, extras, rooms, housekeeping)
├── assets/         css/app.css, js/app.js
├── includes/       header.php, sidebar.php, footer.php
├── sql/            schema.sql, seed.sql
└── index.php       Root redirect
```

## Database Tables

| Table | Purpose |
|-------|---------|
| `users` | Staff accounts (admin / receptionist) |
| `guests` | Guest profiles |
| `room_types` | Room categories with pricing |
| `rooms` | Individual rooms with status |
| `bookings` | Reservations with full status machine |
| `payments` | Payment records per booking |
| `booking_extras` | Add-on services per booking |
| `housekeeping` | Cleaning & maintenance tasks |

## Security Notes

- CSRF tokens on all forms
- Prepared statements (PDO) throughout — no SQL injection
- Password hashing with `password_hash(PASSWORD_BCRYPT)`
- Role enforcement via `Auth::requireRole()`
- `htmlspecialchars()` on all output via `e()`
- Set `DEMO_MODE = false` and change credentials before production use

## Tech Stack

- PHP 8.2+
- MySQL 8.0 / MariaDB 10.6+
- Vanilla JS ES6+
- Chart.js 4 (CDN)
- No CSS frameworks — custom design system
