# System Design Document (SDD)

## 1. Overview

This document describes the architecture and technical design of the **Library Management System**.

- **Backend**: Laravel 11 (PHP 8.3)
- **Admin UI**: Filament v3
- **Database**: MySQL / MariaDB
- **Frontend tooling**: Vite

## 2. System Architecture

### 2.1 Architectural Pattern

- **Client-Server** web application
- **Laravel MVC** for application structure
- **Filament Panels** for Admin/Staff dashboards

### 2.2 High-Level Components

- **Web Client**
  - Browser UI rendered through Filament
- **Application Server**
  - Laravel controllers/routes
  - Filament resources/pages
  - Services (e.g., invoice generation)
- **Database Server**
  - Stores all operational data (users, books, transactions, invoices, stock)

### 2.3 Panel Separation

- Admin panel: `/admin`
- Staff panel: `/staff`

## 3. Database Design

### 3.1 Core Entities (from migrations)

- **users**
- **roles**
- **membership_types**
- **books**
- **authors**
- **genres**
- **publishers**
- **transactions**
- **transaction_items**
- **stock_transactions**
- **stock_transaction_items**
- **invoices**
- **settings**

### 3.2 ER Diagram (To Add)

Add an ER diagram in `docs/assets/er-diagram.png` and reference it here:

- ER Diagram: `docs/assets/er-diagram.png`

Suggested relationships (verify in your models):

- `roles (1) -> (N) users`
- `membership_types (1) -> (N) users` (or invoices depending on design)
- `publishers (1) -> (N) books`
- `authors (N) <-> (N) books` (often many-to-many)
- `genres (N) <-> (N) books` (often many-to-many)
- `transactions (1) -> (N) transaction_items`
- `books (1) -> (N) transaction_items`
- `stock_transactions (1) -> (N) stock_transaction_items`
- `books (1) -> (N) stock_transaction_items`
- `transactions (0..1) -> (0..N) invoices` (depending on business rules)

## 4. Module Design

### 4.1 Authentication & Authorization

- Laravel authentication session
- Role-based redirection implemented at `/` route
- Filament panel access controlled by user role

### 4.2 Catalog Module

- Books
- Authors
- Genres
- Publishers

### 4.3 Circulation Module

- Transactions
- Transaction Items
- Due date handling
- Return workflow
- Fee fields

### 4.4 Stock Module

- Stock Transactions
- Stock Transaction Items

### 4.5 Invoice Module

- Invoice records
- PDF rendering using DomPDF
- PDF preview route: `/invoices/{invoice}/pdf`

### 4.6 Reports Module

- Operational and financial reporting pages

## 5. Interface Design

### 5.1 Wireframes / Screenshots

Add UI screenshots in `docs/assets/ui/` and reference them below.

- Admin dashboard screenshot: `docs/assets/ui/admin-dashboard.png`
- Staff dashboard screenshot: `docs/assets/ui/staff-dashboard.png`

## 6. Deployment Architecture (Typical)

- Nginx/Apache
- PHP-FPM
- MySQL/MariaDB
- Queue worker (optional)
- Scheduler cron for scheduled tasks

## 7. Design Decisions and Trade-offs

- Filament selected for rapid development of admin/staff panels.
- Laravel migrations used to ensure consistent schema evolution across environments.
