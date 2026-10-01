# WarehousePro

A multi-site Warehouse Management System built in PHP 8 + MariaDB.

## Features

- **12 warehouses** with 336 bin locations
- **Inbound** — purchase orders, receive, put-away
- **Outbound** — shipments, FEFO picking, auto tracking, delivered
- **Internal** — transfers between bins and warehouses
- **Quality** — quarantine, release, dispose
- **Cycle count** with variance adjustments
- **Approvals** workflow for sensitive movements
- **Batch tracking** with expiry + FEFO picking
- **Reports** — KPIs, dead stock, top movers, movement trends
- **Audit log** with filters and CSV export
- **Users** — 4 roles (admin, manager, staff, guest), soft delete
- **Shifts** — schedule morning / evening / night
- **Time clock** — staff clock in/out, attendance tracking
- **Failed logins** — security monitoring
- **Messages** — ticket inbox (staff → manager)

## Roles

| Role | What they can do |
|---|---|
| **admin** | Full system governance, users, warehouses, audit |
| **manager** | Operations, shifts, approvals, team, reports |
| **staff** | Daily work — receive, pick, transfer, count, clock in/out |

## Stack

- PHP 8
- MariaDB 10.6
- Apache 2.4 on Ubuntu 22.04
- Bootstrap 5.3, Bootstrap Icons, Inter font
- Flatpickr for date/time pickers

## Setup

1. Clone the repo
2. Copy `config.example.php` → `config.php` and fill in your DB credentials
3. Import the schema (see `docs/` if included)
4. Point Apache at the folder
5. Open `login.php`

## License

Private project — not for public distribution.
