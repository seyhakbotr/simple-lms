# Stock Transaction Module

## Overview

The Stock Transaction module provides a simple yet effective way to track and manage book inventory in the library system. It allows staff to adjust stock levels for **multiple books at once** and maintains a complete audit trail of all stock changes.

## Features

- **Bulk Stock Adjustments**: Adjust stock for multiple books in a single transaction
- **Audit Trail**: Complete history of all stock changes with user tracking
- **Stock Overview**: Dashboard widget showing key stock metrics
- **Book Integration**: View stock history directly from book records
- **Reference Numbers**: Auto-generated unique reference numbers for each transaction

## Database Structure

### Stock Transactions Table (Parent)
Represents a single stock adjustment transaction that can contain multiple books.

```
stock_transactions
├── id (primary key)
├── user_id (foreign key to users - who made the adjustment)
├── type (adjustment type: purchase, damage, lost, donation, correction)
├── reference_number (unique identifier, e.g., ST-20251215-0001)
├── donator_name (name of donator, only for donation type)
├── notes (optional notes for the entire transaction)
└── timestamps (created_at, updated_at)
```

### Stock Transaction Items Table (Children/Line Items)
Individual book adjustments within a transaction.

```
stock_transaction_items
├── id (primary key)
├── stock_transaction_id (foreign key to stock_transactions)
├── book_id (foreign key to books)
├── quantity (amount adjusted)
├── old_stock (stock before adjustment)
├── new_stock (stock after adjustment)
└── timestamps (created_at, updated_at)
```

## Transaction Types

The module supports the following adjustment types:

1. **Purchase** ✅ (Increases stock)
   - Use when acquiring new books through purchase
   - Automatically adds to current stock

2. **Donation** 📦 (Increases stock)
   - Use when receiving donated books
   - Automatically adds to current stock

3. **Damage** ⚠️ (Decreases stock)
   - Use when books are damaged beyond use
   - Automatically subtracts from current stock

4. **Lost** ❌ (Decreases stock)
   - Use when books are lost or missing
   - Automatically subtracts from current stock

5. **Stock Correction** 📝
   - Use to correct inventory discrepancies
   - Sets stock to the exact quantity specified (not add/subtract)

## How to Use

### Creating a Stock Transaction

1. Navigate to **Stock Transactions > Stock Adjustments**
2. Click **New Stock Adjustment**
3. Select the adjustment type (applies to all books in this transaction)
4. **If Donation type:** Enter the donator's name (person or organization)
5. Add optional notes (e.g., "Monthly inventory count" or "Purchased from ABC Books")
6. **Add Books Section:**
   - Click the first row to select a book
   - View the current stock level (displayed automatically)
   - Enter the quantity to adjust
   - Preview the new stock level
   - Click **Add Another Book** to add more books to the same transaction
7. Review all books and quantities
8. Click **Create**

The stock for all books will be updated automatically, and the transaction will be logged with a unique reference number.

### Viewing Stock History

#### From Stock Adjustments Page
- Go to **Stock Transactions > Stock Adjustments**
- View all stock transactions
- Click on any transaction to see all books adjusted in that transaction
- Reference numbers are searchable and copyable

#### From Book Record
1. Go to **Books & Transactions > Books**
2. Click **Edit** on any book
3. Click the **Stock History** tab
4. View all stock transaction items for that specific book
5. See which transaction each adjustment belongs to

### Dashboard Widget

The Stock Overview widget on the admin dashboard displays:
- **Total Books**: Number of unique book titles
- **Total Stock**: Total number of books in inventory
- **Low Stock Books**: Books with less than 10 copies
- **Recent Transactions**: Stock transactions and items adjusted in the last 7 days

## Navigation Structure

```
Stock Transactions (Navigation Group)
└── Stock Adjustments
    ├── List Stock Adjustments
    ├── Create New Adjustment
    └── View Adjustment Details
```

## Permissions & Security

- Stock transactions **cannot be edited** after creation
- Stock transactions **cannot be deleted**
- All transactions are permanently logged with user information
- View-only access to historical records ensures audit integrity
- Each transaction gets a unique reference number for tracking

## Reference Number Format

Reference numbers are auto-generated in the format: `ST-YYYYMMDD-####`

Examples:
- `ST-20251215-0001` - First transaction on Dec 15, 2025
- `ST-20251215-0002` - Second transaction on Dec 15, 2025
- `ST-20251216-0001` - First transaction on Dec 16, 2025

## Examples

### Example 1: Adding Purchased Books (Multiple Books)
**Transaction Type:** Purchase  
**Reference:** ST-20251215-0001  
**Donator:** N/A  
**Notes:** "Purchased from ABC Books - Invoice #12345"

**Books:**
- "The Great Gatsby" - Current: 15, Quantity: 10, New: 25
- "1984" - Current: 8, Quantity: 15, New: 23
- "To Kill a Mockingbird" - Current: 12, Quantity: 8, New: 20

**Result:** All three books' stock updated in a single transaction

### Example 2: Receiving Donated Books
**Transaction Type:** Donation  
**Reference:** ST-20251215-0002  
**Donator:** "John Smith Memorial Foundation"  
**Notes:** "Annual book donation program"

**Books:**
- "The Hobbit" - Current: 10, Quantity: 5, New: 15
- "Harry Potter and the Sorcerer's Stone" - Current: 12, Quantity: 8, New: 20

**Result:** Donated books added with donator tracked

### Example 3: Removing Damaged Books
**Transaction Type:** Damage  
**Reference:** ST-20251215-0003  
**Donator:** N/A  
**Notes:** "Water damage from ceiling leak in storage room"

**Books:**
- "Pride and Prejudice" - Current: 20, Quantity: 3, New: 17
- "Moby Dick" - Current: 15, Quantity: 5, New: 10

**Result:** Both books reduced by specified amounts

### Example 4: Monthly Stock Correction
**Transaction Type:** Correction  
**Reference:** ST-20251215-0004  
**Donator:** N/A  
**Notes:** "Monthly physical inventory count - December 2025"

**Books:**
- "The Catcher in the Rye" - Current: 23, Quantity: 20, New: 20
- "Brave New World" - Current: 18, Quantity: 18, New: 18 (no change)
- "Lord of the Flies" - Current: 14, Quantity: 15, New: 15

**Result:** Stock set to exact counted amounts

## Best Practices

1. **Group related adjustments**: Put all books from the same purchase order or event in one transaction
2. **Add detailed notes**: Include supplier name, invoice number, or reason for adjustment
3. **Track donators**: Always enter the donator's name for donation transactions to acknowledge contributions
4. **Use correction sparingly**: Only for fixing errors during physical counts, not regular adjustments
5. **Review before submitting**: Check all quantities and books before creating the transaction
6. **Regular audits**: Use stock correction type during monthly/quarterly physical counts
7. **Reference numbers**: Copy and save reference numbers for your records

## Files Created

### Models
- `app/Models/StockTransaction.php` - Parent transaction model
- `app/Models/StockTransactionItem.php` - Child item model (individual books)
- `app/Enums/StockAdjustmentType.php` - Adjustment type enum

### Resources
- `app/Filament/Admin/Resources/StockTransactionResource.php` - Main resource
- `app/Filament/Admin/Resources/StockTransactionResource/Pages/`
  - `ListStockTransactions.php`
  - `CreateStockTransaction.php`
  - `ViewStockTransaction.php`

### Relation Managers
- `app/Filament/Admin/Resources/BookResource/RelationManagers/StockTransactionsRelationManager.php`

### Widgets
- `app/Filament/Admin/Widgets/StockOverview.php` - Dashboard stats widget

### Migrations
- `database/migrations/2025_12_15_160225_create_stock_transactions_table.php`
- `database/migrations/2025_12_15_160226_create_stock_transaction_items_table.php`

## Technical Notes

### Transaction Processing

Stock adjustments are processed atomically using database transactions:

1. Parent `StockTransaction` record is created
2. For each book in the transaction:
   - Current stock is retrieved
   - New stock is calculated based on adjustment type
   - `StockTransactionItem` is created
   - Book's stock is updated
3. If any step fails, all changes are rolled back

### Stock Calculation Logic

```php
// For Purchase and Donation
$newStock = $currentStock + $quantity;

// For Damage and Lost
$newStock = max(0, $currentStock - $quantity);

// For Correction
$newStock = $quantity; // Set to exact value
```

### Duplicate Book Prevention

The form prevents selecting the same book multiple times in a single transaction using the `disableOptionWhen` callback.

## Future Enhancements (Optional)

If you need to expand this module in the future, consider:

- **Bulk import**: Upload CSV for large stock adjustments
- **Stock alerts**: Email notifications when inventory is low
- **Auto-adjustment**: Automatic stock reduction on book borrowing
- **Stock reports**: Detailed analytics and trends
- **Supplier management**: Link transactions to suppliers
- **Barcode scanning**: Faster book selection
- **Approval workflow**: Require manager approval for large adjustments

---

**Module Created:** December 15, 2025  
**Compatible With:** Filament 3.x, Laravel 10+  
**Structure:** Parent-Child (stock_transactions → stock_transaction_items)