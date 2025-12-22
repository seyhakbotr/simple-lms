# Project Plan — Work Breakdown Structure (WBS) and Schedule

## 1. Work Breakdown Structure (WBS)

### 1.0 Planning

- 1.1 Requirements gathering (stakeholder interviews, scope definition)
- 1.2 Tool selection (Laravel 11, Filament v3, MySQL/MariaDB, Vite)
- 1.3 Project repository setup (Git, branching strategy)
- 1.4 Initial risk assessment

### 2.0 Analysis & Design

- 2.1 Database design
  - 2.1.1 Identify entities: Users, Roles, Membership Types, Books, Authors, Genres, Publishers
  - 2.1.2 Circulation entities: Transactions, Transaction Items
  - 2.1.3 Stock entities: Stock Transactions, Stock Transaction Items
  - 2.1.4 Finance entities: Invoices
- 2.2 UI/UX design (Admin and Staff panel navigation, forms)
- 2.3 System architecture design (Client-server, Laravel MVC, Filament panels)

### 3.0 Development

- 3.1 Environment setup
  - 3.1.1 `.env` configuration
  - 3.1.2 Database setup
  - 3.1.3 Migrations + seeders
- 3.2 Authentication & authorization
  - 3.2.1 Login flow
  - 3.2.2 Role-based redirects (Admin -> `/admin`, Staff -> `/staff`)
  - 3.2.3 Permission rules
- 3.3 Core master data modules
  - 3.3.1 Books module
  - 3.3.2 Authors module
  - 3.3.3 Genres module
  - 3.3.4 Publishers module
- 3.4 Users & memberships
  - 3.4.1 Users management
  - 3.4.2 Roles management
  - 3.4.3 Membership Types
- 3.5 Circulation
  - 3.5.1 Transactions (create/view/edit)
  - 3.5.2 Transaction items
  - 3.5.3 Returns workflow
  - 3.5.4 Due date & fees logic
- 3.6 Stock management
  - 3.6.1 Stock transactions
  - 3.6.2 Stock transaction items
- 3.7 Invoicing
  - 3.7.1 Invoice generation
  - 3.7.2 Invoice PDF preview route
- 3.8 Reports
  - 3.8.1 Book reports
  - 3.8.2 Transaction reports
  - 3.8.3 Inventory reports
  - 3.8.4 Financial reports
- 3.9 Localization
  - 3.9.1 Locale switch (`en`, `km`)

### 4.0 Testing

- 4.1 Unit tests (models/services)
- 4.2 Feature tests (core workflows)
- 4.3 Regression testing
- 4.4 Bug fixing and re-test

### 5.0 Deployment & Closing

- 5.1 Production build and deployment (hosting)
- 5.2 Database backup/export (SQL dump)
- 5.3 Documentation finalization
- 5.4 Project closing report

## 2. Schedule (Template)

> Create a Gantt chart in Excel / MS Project / TeamGantt using the WBS above.

Suggested columns:

- Task ID
- Task Name
- Start Date
- End Date
- Duration
- Owner
- Dependencies
- Status

Example milestones (edit dates):

- M1: Requirements & scope approved
- M2: Database schema finalized
- M3: Admin panel modules completed
- M4: Staff panel circulation completed
- M5: Testing completed
- M6: Deployment + final demo completed
