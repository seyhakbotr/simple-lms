# Library Management System — Documentation Index

This folder contains the project documentation artifacts for the **Library Management System** (Laravel 11 + Filament v3)

## Documents

- **01-Project-Proposal.md**
- **02-SRS.md**
- **03-Project-Plan-WBS.md**
- **04-SDD.md**
- **05-Test-Plan.md**
- **06-Test-Summary-Report.md**
- **07-Project-Closing-Report.md**
- **User-Manual.md**
- **Status-Reports/**
- **Presentation-Slides-Outline.md**

## System snapshot (from the current codebase)

- **Application type**: Web-based Library Management System
- **Backend framework**: Laravel `^11.39.1`
- **Admin UI**: Filament `^3.2.x` (separate **Admin** and **Staff** panels)
- **Frontend tooling**: Vite `^5`, Axios `^1.6`
- **Database**: MySQL / MariaDB
- **Key modules found**:
  - Books, Authors, Genres, Publishers
  - Users, Roles, Membership Types
  - Transactions (checkout/return/renew, due dates, fees)
  - Stock Transactions
  - Invoices + PDF preview
  - Reports
  - General settings
- **Key database tables (from migrations)**:
  - `users`, `roles`, `membership_types`
  - `books`, `authors`, `genres`, `publishers`
  - `transactions`, `transaction_items`
  - `stock_transactions`, `stock_transaction_items`
  - `invoices`, `settings`


