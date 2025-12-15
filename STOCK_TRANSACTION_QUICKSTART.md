# Stock Transaction Quick Start Guide

## Installation Complete ✅

The Stock Transaction module has been successfully installed in your library system!

## What Was Added

### 1. Stock Adjustments Page
- **Location:** Stock Transactions > Stock Adjustments
- **Purpose:** Create and view all stock transactions (multiple books per transaction)

### 2. Stock History on Books
- **Location:** Books > Edit Book > Stock History tab
- **Purpose:** View stock transaction history for each individual book

### 3. Dashboard Widget
- **Location:** Admin Dashboard
- **Purpose:** Quick overview of stock metrics

## Quick Start (3 Steps)

### Step 1: Access Stock Adjustments
Navigate to **Stock Transactions > Stock Adjustments** in the admin panel

### Step 2: Create Your First Transaction
1. Click **"New Stock Adjustment"**
2. Select adjustment type (applies to all books in this transaction)
3. Add optional notes (e.g., "Monthly purchase from ABC Books")
4. **Add Books:**
   - Select a book from the dropdown (shows title, author, and ISBN)
   - Book details display automatically: ISBN, Author, Current Stock
   - Enter quantity to adjust
   - Preview new stock
   - Click **"Add Another Book"** to add more books
5. Review all books and quantities
6. Click **Create**

### Step 3: View Results
- Transaction is created with unique reference number (e.g., ST-20251215-0001)
- All book stocks are updated automatically
- Check the Stock History tab on any book to see the changes
- Dashboard widget shows updated metrics

## Key Features

### ✨ Multiple Books Per Transaction
Instead of adjusting books one at a time, you can now:
- Purchase multiple book titles in one transaction
- Record damage to several books at once
- Perform monthly inventory counts efficiently
- Group related adjustments together

### 📖 Enhanced Book Display
When adding books to a transaction:
- **Dropdown shows**: Title - Author (ISBN: ###)
- **Auto-displays**: ISBN, Author, and Current Stock
- **Easy identification**: Find books by title, author, or ISBN
- **Copy ISBN**: Click to copy ISBN codes

### 📋 Reference Numbers
Every transaction gets a unique reference number:
- Format: `ST-YYYYMMDD-####`
- Example: `ST-20251215-0001`
- Easy to search, copy, and track

### 🔒 Audit Trail
- Cannot edit or delete transactions after creation
- All changes permanently logged
- User tracking on every transaction
- Complete history for compliance

## Adjustment Types Cheat Sheet

| Type | Effect | Use When | Example |
|------|--------|----------|---------|
| 🛒 **Purchase** | Adds to stock | Buying new books | Added 10 copies from supplier |
| 🎁 **Donation** | Adds to stock | Receiving donated books | Library received 5 donated books |
| ⚠️ **Damage** | Subtracts from stock | Books damaged beyond use | Water damage to 3 copies |
| ❌ **Lost** | Subtracts from stock | Books lost or missing | 2 books missing from shelves |
| 📝 **Correction** | Sets exact amount | Fixing inventory errors | Physical count shows 15, not 18 |

## Example Scenarios

### Scenario 1: Monthly Book Purchase (3 Different Titles)
**Transaction Type:** Purchase  
**Donator:** N/A  
**Notes:** "December 2025 order from ABC Books - Invoice #12345"

**Books Added:**
1. "Pride and Prejudice" - Quantity: 10 (stock: 15 → 25)
2. "1984" - Quantity: 8 (stock: 12 → 20)
3. "The Great Gatsby" - Quantity: 15 (stock: 5 → 20)

**Result:** One transaction adjusts all three books

---

### Scenario 2: Receiving Book Donation
**Transaction Type:** Donation  
**Donator:** "John Smith Memorial Foundation"  
**Notes:** "Annual community book donation program"

**Books Received:**
1. "The Hobbit" - Quantity: 5 (stock: 10 → 15)
2. "Harry Potter and the Sorcerer's Stone" - Quantity: 8 (stock: 12 → 20)

**Result:** Donated books added with donator tracked

---

### Scenario 3: Water Damage Incident
**Transaction Type:** Damage  
**Donator:** N/A  
**Notes:** "Ceiling leak in storage room B - December 15"

**Books Affected:**
1. "To Kill a Mockingbird" - Quantity: 5 (stock: 20 → 15)
2. "Brave New World" - Quantity: 3 (stock: 18 → 15)
3. "Lord of the Flies" - Quantity: 2 (stock: 12 → 10)

**Result:** All damaged books recorded in single incident

---

### Scenario 4: Monthly Inventory Count
**Transaction Type:** Correction  
**Donator:** N/A  
**Notes:** "December 2025 physical inventory count"

**Books Corrected:**
1. "The Catcher in the Rye" - Quantity: 20 (stock: 23 → 20)
2. "Moby Dick" - Quantity: 18 (stock: 18 → 18) *no change*
3. "Animal Farm" - Quantity: 15 (stock: 14 → 15)

**Result:** Stock corrected to match physical count

## Dashboard Metrics

The Stock Overview widget shows:
- 📚 **Total Books** - Number of unique book titles
- 📦 **Total Stock** - Total number of books in inventory
- ⚠️ **Low Stock** - Books with less than 10 copies (warning indicator)
- 🔄 **Recent Transactions** - Stock changes in the last 7 days

## Important Notes

⚠️ **Stock transactions cannot be edited or deleted** - Ensures audit integrity

✅ **All changes are logged permanently** - Every transaction records who made it and when

📊 **Real-time updates** - Book stock updates immediately after creating a transaction

🚫 **No duplicates** - Cannot add the same book twice in one transaction

💡 **Preview before saving** - See new stock levels before submitting

## Tips for Success

### 1. Group Related Adjustments
Put all books from the same event in one transaction:
- All books from same purchase order
- All books damaged in same incident
- All books counted during same inventory session

### 2. Add Detailed Notes
Include useful information:
- Supplier name and invoice number for purchases
- **Donator name for donations** (required field)
- Reason and location for damage/loss
- Date and person who performed physical count

### 3. Use Appropriate Type
- **Purchase/Donation**: When adding new books
- **Damage/Lost**: When removing unusable books
- **Correction**: ONLY for fixing counting errors during physical inventory

### 4. Review Before Submitting
- Double-check all book selections
- Verify quantities are correct
- Preview new stock levels
- Ensure notes are complete

### 5. Save Reference Numbers
- Copy the reference number after creating transaction
- Keep with your paper records
- Use for cross-referencing invoices or reports

## Common Workflows

### 📦 Receiving New Books
1. Click "New Stock Adjustment"
2. Type: **Purchase**
3. Notes: "Supplier name + Invoice number"
4. Add each book title with quantity received
5. Submit

### 🎁 Recording Donated Books
1. Click "New Stock Adjustment"
2. Type: **Donation**
3. **Donator Name:** Enter person or organization name
4. Notes: "Donation program or occasion"
5. Add each donated book with quantity
6. Submit

### 🔍 Monthly Inventory Count
1. Physically count books on shelves
2. Click "New Stock Adjustment"
3. Type: **Correction**
4. Notes: "Monthly count - [Month Year]"
5. Add each book with actual counted quantity
6. Submit

### 📝 Recording Damaged Books
1. Click "New Stock Adjustment"
2. Type: **Damage** or **Lost**
3. Notes: Explain what happened
4. Add affected books with quantity removed
5. Submit

## Viewing History

### View All Transactions
1. Go to **Stock Transactions > Stock Adjustments**
2. Browse all transactions
3. Search by reference number or book
4. Click to view details

### View Book-Specific History
1. Go to **Books > Books**
2. Click **Edit** on a book
3. Click **Stock History** tab
4. See all adjustments for that book with:
   - Reference number
   - Transaction type
   - ISBN (copyable)
   - Quantity adjusted
   - Old and new stock
   - Who made the adjustment
5. Each row shows the transaction it belongs to

## Database Structure

```
stock_transactions (Parent)
├── Reference: ST-20251215-0001
├── Type: Purchase
├── Donator: NULL (only for donations)
├── Notes: "Order from ABC Books"
└── Items: (Children)
    ├── Book: "Pride and Prejudice" | Qty: 10 | Old: 15 | New: 25
    ├── Book: "1984" | Qty: 8 | Old: 12 | New: 20
    └── Book: "The Great Gatsby" | Qty: 15 | Old: 5 | New: 20

Example Donation:
stock_transactions (Parent)
├── Reference: ST-20251215-0002
├── Type: Donation
├── Donator: "John Smith Memorial Foundation"
├── Notes: "Annual community donation"
└── Items: (Children)
    ├── Book: "The Hobbit" | Qty: 5 | Old: 10 | New: 15
    └── Book: "Harry Potter" | Qty: 8 | Old: 12 | New: 20
```

## Need Help?

See the full documentation in `STOCK_TRANSACTION.md` for:
- Technical details
- Database structure
- API information
- Advanced features

---

**Ready to go!** Start by creating your first stock transaction with multiple books. 🚀

**Pro Tip:** Start with a small purchase or donation to get familiar with the system before doing a full inventory count.