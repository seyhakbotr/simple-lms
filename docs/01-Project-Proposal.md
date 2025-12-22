# Project Proposal — Library Management System

## 1. Introduction / Background

The current manual or semi-manual approach to managing library operations (book cataloging, member records, checkouts/returns, overdue tracking, and fee calculation) is inefficient and error-prone. Common issues include:

- Slow search and retrieval of book/member records
- Inconsistent tracking of circulation status and due dates
- Difficulty generating invoices and operational reports
- Limited visibility into inventory/stock movement

This project proposes a **web-based Library Management System** to centralize and automate these workflows.

## 2. Problem Statement

Manual library processes lead to longer processing times, data duplication, and reduced accountability. Staff may also struggle to produce accurate financial and inventory reports on demand.

## 3. Proposed Solution

Develop a web-based application using **Laravel 11** with **Filament Admin Panels** to provide:

- Role-based access for **Admin** and **Staff**
- Book catalog management (books, authors, genres, publishers)
- Member and staff account management
- Circulation workflows (checkout, return, renewals, due dates)
- Fees and invoice generation with PDF preview
- Stock transaction tracking
- Reports dashboards for operations

## 4. Objectives

- Reduce transaction processing time (checkout/return/invoicing) by streamlining staff workflows.
- Improve accuracy of inventory and circulation tracking.
- Provide real-time reports (inventory, circulation, finance, members).
- Implement role-based access control for secure operations.

## 5. Scope

### 5.1 In-Scope

- Admin and Staff web panels
- User and role management
- Book catalog management:
  - Books, Authors, Genres, Publishers
- Membership management:
  - Membership Types, member assignment
- Circulation:
  - Transactions, Transaction Items, due dates, status lifecycle
- Fees management and invoicing:
  - Invoice generation and PDF preview
- Stock management:
  - Stock Transactions and Stock Transaction Items
- Reports module
- Basic localization switching (`en`, `km`)

### 5.2 Out-of-Scope (Phase 2 / Future)

- Dedicated mobile app
- Online public OPAC (public searchable catalog) if not already present
- Third-party payment gateway integration (if required later)

## 6. Methodology

- Agile Scrum with 2-week sprints
- Sprint activities:
  - Planning, implementation, review, and retrospective
- Tooling:
  - Git for version control
  - Automated tests (Pest / PHPUnit) where applicable

## 7. Deliverables

- Source code repository (clean structure, README, installation steps)
- Database migrations/seeders and optional SQL dump
- Documentation artifacts (Proposal, SRS, SDD, Test Plan, etc.)
- Final deployed web application URL (if hosting is required)

## 8. High-Level Timeline (Example)

- Week 1-2: Requirements + environment setup + initial DB design
- Week 3-6: Core modules (Books, Users/Roles, Members)
- Week 7-9: Circulation + Fees + Invoices
- Week 10-11: Stock + Reports
- Week 12: Testing + polishing + deployment + final presentation

## 9. Expected Outcomes

- Faster, more reliable library operations
- Centralized tracking of books, members, circulation, inventory, and invoices
- Improved auditability through transaction history and reports

## 10. References

- Laravel Documentation: https://laravel.com/docs
- Filament Documentation: https://filamentphp.com/docs
- IEEE 830 SRS guidance (structure reference)
