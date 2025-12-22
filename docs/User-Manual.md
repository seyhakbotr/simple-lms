# User Manual — Library Management System

## 1. Getting Started

### 1.1 Requirements

- A modern web browser
- Access URL to the deployed system (or `http://localhost:8000` for local)

### 1.2 Logging In

1. Open the system URL
2. Enter your email and password
3. Click **Login**

After login:

- Admin users are redirected to `/admin`
- Staff users are redirected to `/staff`

Add screenshot:

- `docs/assets/user-manual/login.png`

## 2. Navigation Basics

- Use the left-side menu to open modules (Books, Transactions, Invoices, Reports, etc.)
- Use the top bar for profile and logout

## 3. Common Tasks

### 3.1 Add a New Book (Admin/Staff)

1. Open **Books**
2. Click **Create**
3. Fill in book information (Title, ISBN, Author, Genre, Publisher, etc.)
4. Click **Save**

Add screenshot:

- `docs/assets/user-manual/create-book.png`

### 3.2 Register a Member / Assign Membership (Admin)

1. Open **Users**
2. Create or edit a user
3. Assign the appropriate **Membership Type**
4. Save

### 3.3 Checkout a Book (Staff)

1. Open **Transactions**
2. Click **Create**
3. Select member
4. Add one or more books as transaction items
5. Confirm and save

Expected result:

- Transaction created
- Due date is set (based on configured rules)

### 3.4 Return a Book (Staff)

1. Open **Transactions**
2. Open a transaction
3. Use **Return** action/page
4. Confirm return

Expected result:

- Status updated to returned
- Fee calculated if overdue (if enabled)

### 3.5 View / Print an Invoice PDF

1. Open **Invoices**
2. Open an invoice
3. Click **PDF Preview** (or open the PDF preview link)

Expected result:

- PDF opens/streams in your browser

## 4. Troubleshooting

### 4.1 Can’t log in

- Verify email/password
- Contact Admin to reset credentials

### 4.2 PDF preview not working

- Confirm server has required PHP extensions
- Confirm storage permissions

### 4.3 Error 500

- Refresh the page
- If persists, contact Admin and provide time/date and action performed

## 5. FAQ

- Q: What is the difference between Admin and Staff?
  - A: Admin manages configuration and master data; Staff focuses on daily operations.
