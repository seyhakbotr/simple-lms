# Software Requirements Specification (SRS)

## 1. Introduction

### 1.1 Purpose

This SRS defines the functional and non-functional requirements of the **Library Management System** implemented as a **web-based application** using Laravel and Filament.

### 1.2 Intended Audience

- Project supervisor / instructor
- Developers and maintainers
- QA testers
- Library Admin and Staff stakeholders

### 1.3 System Overview

The system provides **Admin** and **Staff** web panels to manage:

- Books and related entities (authors, genres, publishers)
- Users, roles, and membership types
- Circulation transactions (checkout/return/renew, due dates)
- Fees and invoice generation (including PDF preview)
- Stock transactions and inventory adjustments
- Operational reports

## 2. Overall Description

### 2.1 Product Perspective

- Client-server web application
- Server-side rendered admin UI via Filament
- Database-backed using MySQL/MariaDB

### 2.2 User Classes and Characteristics

- **Admin**
  - Full access to configuration, roles, and management modules
- **Staff**
  - Operational access (circulation, viewing/creating records based on permissions)

### 2.3 Assumptions and Dependencies

- Users have access to a modern web browser.
- Server environment provides PHP 8.3+, Composer, and MySQL/MariaDB.
- Email features (password reset / notifications) require SMTP configuration if used.

## 3. Functional Requirements

> Format: `R#.#` with “The system shall …” statements.

### 3.1 Authentication & Access Control

- **R1.1**: The system shall allow users to log in using email and password.
- **R1.2**: The system shall route authenticated users to the correct panel based on role (Admin -> `/admin`, Staff -> `/staff`).
- **R1.3**: The system shall restrict access to modules based on role and permissions.

### 3.2 User and Role Management

- **R2.1**: The system shall allow Admin to create, edit, and deactivate staff accounts.
- **R2.2**: The system shall allow Admin to manage roles.

### 3.3 Catalog Management

- **R3.1**: The system shall allow users to create, view, update, and delete Books.
- **R3.2**: The system shall allow users to manage Authors, Genres, and Publishers.
- **R3.3**: The system shall store book metadata (e.g., title, ISBN, etc.) as defined by the database schema.

### 3.4 Membership Management

- **R4.1**: The system shall allow Admin to define Membership Types.
- **R4.2**: The system shall allow assigning membership information to Users (members).

### 3.5 Circulation (Transactions)

- **R5.1**: The system shall allow staff to create a checkout transaction for a member.
- **R5.2**: The system shall record transaction items per transaction.
- **R5.3**: The system shall compute and store due dates.
- **R5.4**: The system shall allow staff to process returns.
- **R5.5**: The system shall support transaction lifecycle statuses.

### 3.6 Fees and Invoicing

- **R6.1**: The system shall allow staff/admin to generate invoices linked to transactions where applicable.
- **R6.2**: The system shall provide an invoice PDF preview endpoint.
- **R6.3**: The system shall store invoice details including invoice number and membership type association.

### 3.7 Stock Management

- **R7.1**: The system shall record stock transactions and stock transaction items.
- **R7.2**: The system shall maintain before/after quantities for audit purposes.

### 3.8 Reporting

- **R8.1**: The system shall provide operational reports including book, transaction, member, inventory, and financial reporting.

### 3.9 Localization

- **R9.1**: The system shall allow switching locale between `en` and `km`.

## 4. Non-Functional Requirements

### 4.1 Performance

- **NFR1**: The system should load key dashboard pages in under 2 seconds under normal load.

### 4.2 Security

- **NFR2**: Passwords shall be hashed using a secure hashing algorithm.
- **NFR3**: The system shall enforce CSRF protection on state-changing requests.
- **NFR4**: The system shall implement role-based authorization for protected routes.

### 4.3 Reliability & Availability

- **NFR5**: The system should provide consistent data persistence and recoverability through database backups.

### 4.4 Maintainability

- **NFR6**: The system shall follow Laravel conventions and use migrations for schema changes.

### 4.5 Usability

- **NFR7**: The system UI shall be responsive and usable on desktop and mobile browsers.

## 5. External Interface Requirements

### 5.1 User Interface

- Admin and Staff panels implemented via Filament.

### 5.2 Software Interfaces

- MySQL/MariaDB database
- Optional SMTP service for emails

## 6. Use Cases (Textual)

### UC-01: Login and Redirect

- **Actor**: User (Admin/Staff)
- **Preconditions**: User account exists
- **Main Flow**:
  1. User enters credentials
  2. System authenticates
  3. System redirects to `/admin` or `/staff` depending on role
- **Postconditions**: Authenticated session started

### UC-02: Create a Book Transaction (Checkout)

- **Actor**: Staff
- **Preconditions**: Member exists, book exists and is available
- **Main Flow**:
  1. Staff creates a transaction
  2. Staff adds one or more transaction items
  3. System calculates due date and stores it
- **Postconditions**: Transaction saved; items reserved/checked out

## 7. Appendix / References

- IEEE 830 SRS template (structure reference)
- Laravel docs: https://laravel.com/docs
- Filament docs: https://filamentphp.com/docs
