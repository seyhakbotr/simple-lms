# Test Plan — Library Management System

## 1. Objective

Define the testing approach to verify the Library Management System meets requirements and is ready for submission/deployment.

## 2. Scope

### In Scope

- Authentication and role-based redirection
- Admin panel modules (CRUD, settings)
- Staff circulation workflows (checkout/return)
- Stock transactions
- Invoice generation and PDF preview
- Reports pages (smoke testing)

### Out of Scope

- Performance/load testing at scale (unless required)
- Mobile app testing (not applicable)

## 3. Test Strategy

- **Unit testing** for services and helpers
- **Feature testing** for end-to-end workflows
- **Manual UI testing** for Filament screens

Recommended techniques:

- Boundary Value Analysis (e.g., due dates, fee amounts)
- Equivalence Partitioning (valid/invalid input)
- Role-based authorization testing

## 4. Test Environment

- PHP 8.3
- Laravel 11
- MySQL/MariaDB
- Local dev server (`php artisan serve`)

## 5. Tools

- Pest / PHPUnit (already present in `composer.json` dev dependencies)
- Browser manual testing

## 6. Entry / Exit Criteria

### Entry Criteria

- Migrations and seeders run successfully
- No critical runtime errors on login and dashboard

### Exit Criteria

- All critical test cases pass
- No open critical bugs
- Test summary report completed

## 7. Test Cases (Examples)

### TC01 — Login (Valid Credentials)

- **Precondition**: User exists
- **Input**: Valid email and password
- **Expected Result**: User logged in and redirected to correct panel
- **Status**: ____

### TC02 — Login (Invalid Password)

- **Input**: Valid email, wrong password
- **Expected Result**: Error message displayed (invalid credentials)
- **Status**: ____

### TC03 — Admin Access Control

- **Actor**: Staff
- **Steps**: Attempt to access `/admin`
- **Expected Result**: Access denied or redirected
- **Status**: ____

### TC04 — Create Book

- **Actor**: Admin
- **Steps**: Create a new book record
- **Expected Result**: Book saved and visible in listing
- **Status**: ____

### TC05 — Checkout Transaction

- **Actor**: Staff
- **Steps**: Create transaction with at least one book item
- **Expected Result**: Transaction saved; due date populated; book status updated (if applicable)
- **Status**: ____

### TC06 — Return Transaction

- **Actor**: Staff
- **Steps**: Return checked-out item
- **Expected Result**: Status updated; fees computed if overdue
- **Status**: ____

### TC07 — Invoice PDF Preview

- **Actor**: Staff/Admin
- **Steps**: Open `/invoices/{invoice}/pdf`
- **Expected Result**: PDF streams successfully
- **Status**: ____

## 8. Defect Reporting

Track defects using:

- Defect ID
- Steps to reproduce
- Expected vs actual result
- Severity
- Status
- Fix version
