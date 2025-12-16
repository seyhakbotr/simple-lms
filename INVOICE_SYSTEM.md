# Invoice System Documentation

## Overview

The Invoice System automatically generates invoices when books are returned with fees. It tracks all library fees (overdue, lost books, and damage fees), manages payments, and provides complete financial records for both staff and members.

## Key Features

### 1. **Automatic Invoice Generation**
- Invoices are automatically created when books are returned with fees
- Generated at the time of return transaction completion
- Unique invoice numbers (format: `INV-YYYYMMDD-XXXX`)
- Only created if total fees > $0

### 2. **Fee Tracking**
- **Overdue Fees**: Late return charges based on days overdue
- **Lost Book Fees**: Replacement cost for unreturned books
- **Damage Fees**: Manually entered charges for damaged books
- Complete breakdown stored in each invoice

### 3. **Payment Management**
- Record full or partial payments
- Multiple payment methods supported
- Payment history tracked in invoice notes
- Automatic status updates (unpaid → partially paid → paid)

### 4. **Invoice Waiving**
- Staff can waive invoices with documented reasons
- Useful for exceptional circumstances
- Maintains audit trail

---

## How It Works

### Invoice Generation Flow

```
Book Return → Calculate Fees → Generate Invoice → Notify Member
     ↓              ↓                 ↓               ↓
Transaction    Overdue Fee      INV-20251216-0001   Email/SMS
  Returns      Lost Fee         Status: Unpaid      (Future)
               Damage Fee       Due: 30 days
```

### Automatic Creation

When a transaction is returned via the **Return Books** process:

1. System calculates all applicable fees
2. If total fees > $0:
   - Creates invoice automatically
   - Links to transaction and user
   - Sets due date (default: 30 days from return)
   - Status: Unpaid
3. If total fees = $0:
   - No invoice created
   - Transaction marked as clean return

**Example:**
```
Transaction: TXN-20251216-0001
Returned: Dec 16, 2025
Fees:
  - Overdue: $5.00 (2 days late)
  - Lost: $0.00
  - Damage: $0.00
Total: $5.00

→ Invoice INV-20251216-0001 created
   Due Date: Jan 15, 2026
   Status: Unpaid
```

---

## Invoice Statuses

### Status Lifecycle

```
┌─────────┐
│ UNPAID  │ ← Initial state (no payment received)
└────┬────┘
     │ Record partial payment
     ▼
┌────────────────┐
│ PARTIALLY_PAID │ ← Some payment received
└────────┬───────┘
         │ Pay remaining balance
         ▼
    ┌────────┐
    │  PAID  │ ← Fully paid
    └────────┘

    Alternative path:
    ┌────────┐
    │ WAIVED │ ← Staff waived invoice
    └────────┘
```

### Status Descriptions

| Status | Description | Amount Due | Can Accept Payment? |
|--------|-------------|------------|---------------------|
| **Unpaid** | No payment received | Full amount | Yes |
| **Partially Paid** | Some payment received | Remaining balance | Yes |
| **Paid** | Fully paid | $0.00 | No |
| **Waived** | Fees forgiven | $0.00 | No |

---

## Managing Invoices

### Accessing Invoices

**Admin Panel:**
1. Navigate to **Finance → Invoices**
2. View all invoices with filtering options
3. Click any invoice to view details

**Tabs Available:**
- **All Invoices**: Complete list
- **Unpaid**: Outstanding invoices with no payment
- **Partially Paid**: Invoices with some payment
- **Overdue**: Past due date and still unpaid
- **Paid**: Fully settled invoices
- **Waived**: Forgiven invoices

### Recording Payments

**From Invoice List:**
1. Find the invoice
2. Click **Record Payment** action
3. Enter payment details:
   - Amount (cannot exceed amount due)
   - Payment method (cash, card, check, etc.)
   - Optional notes
4. Click **Submit**

**From Invoice View:**
1. Open invoice details
2. Click **Record Payment** button in header
3. Fill payment form
4. Submit

**Payment Methods:**
- Cash
- Credit/Debit Card
- Check
- Bank Transfer
- Online Payment

**Example:**
```
Invoice: INV-20251216-0001
Amount Due: $25.00

Record Payment:
- Amount: $10.00
- Method: Cash
- Notes: "First installment"

Result:
- Amount Paid: $10.00
- Amount Due: $15.00
- Status: Partially Paid
```

### Waiving Invoices

Only authorized staff can waive invoices.

**Process:**
1. Open invoice
2. Click **Waive Invoice** button
3. Provide reason (required):
   - First-time offender
   - System error
   - Special circumstances
   - Goodwill gesture
4. Confirm waiver

**Important:**
- Waiving is permanent
- Reason is stored in invoice notes
- Cannot waive already-paid invoices
- Creates audit trail

---

## Invoice Details

### Information Displayed

**Invoice Header:**
- Invoice Number (unique identifier)
- Status badge (color-coded)
- Transaction reference (linked)

**Borrower Information:**
- Name
- Email
- Membership Type

**Fee Breakdown:**
- Overdue Fee amount
- Lost Book Fee amount
- Damage Fee amount
- **Total Amount** (sum of all fees)

**Payment Information:**
- Amount Paid (so far)
- Amount Due (remaining)
- Paid At (date/time when fully paid)

**Important Dates:**
- Invoice Date (when generated)
- Due Date (payment deadline)
- Created At (timestamp)

**Transaction Details:**
- Transaction reference
- Borrow date
- Due date
- Return date
- List of books with status

---

## Fee Calculation

Invoices store fees calculated during the return process:

### Overdue Fees

Based on return date vs. due date:

```
Days Late = Return Date - Due Date
Chargeable Days = Days Late - Grace Period
Overdue Fee = Chargeable Days × Fee Per Day
```

With caps applied:
- Maximum days charged
- Maximum total amount

**Example:**
```
Settings:
- Fee Per Day: $2.50
- Grace Period: 2 days
- Max Days: 30
- Max Amount: $50.00

Scenario:
- Due Date: Dec 1, 2025
- Return Date: Dec 10, 2025
- Days Late: 9 days
- Chargeable: 7 days (9 - 2 grace)
- Calculated: $17.50 (7 × $2.50)
- Invoice Fee: $17.50
```

### Lost Book Fees

Calculated when book is marked as lost:

**Percentage Method:**
```
Base Fee = Book Price × (Percentage / 100)
Final Fee = Constrained by Min/Max limits
```

**Fixed Method:**
```
Final Fee = Fixed Amount
```

**Example:**
```
Book Price: $35.00
Settings: 100% of price, Min: $10, Max: $100

Lost Fee = $35.00 (within limits)
```

### Damage Fees

Manually entered by staff at return time:

```
Damage Fee = Amount entered by staff
Damage Notes = Description of damage
```

**Example:**
```
Book returned with water damage
Staff Assessment: $8.00
Notes: "Water stains on pages 10-20"

Invoice Damage Fee: $8.00
```

---

## Reports and Statistics

### Dashboard Widgets

The invoice list page shows:

1. **Total Outstanding**
   - Sum of all unpaid amounts
   - Trend chart (6 months)
   - Critical metric for financial health

2. **Total Collected**
   - All-time revenue from fees
   - Shows payment processing success

3. **Overdue Invoices**
   - Count of past-due invoices
   - Requires immediate attention

4. **This Month**
   - Invoices generated this month
   - Activity indicator

5. **Month Revenue**
   - Payments received this month
   - Current period performance

### Filtering and Searching

**Search by:**
- Invoice number
- Borrower name
- Transaction reference

**Filter by:**
- Status (unpaid, partially paid, paid, waived)
- Overdue status (toggle)
- Date range (invoice date)
- User

**Sort by:**
- Invoice date
- Due date
- Total amount
- Amount due
- Status

---

## User Perspective

### For Borrowers

When you return books late or with issues:

1. **Return Process**
   - Staff processes return
   - Fees calculated automatically
   - You receive invoice number

2. **Invoice Notification**
   - Invoice number: INV-XXXXXXXX-XXXX
   - Total amount due
   - Payment due date
   - How to pay

3. **Payment Options**
   - Pay in full at library
   - Partial payments accepted
   - Multiple payment methods
   - Pay before due date to avoid overdue status

4. **Check Invoice Status**
   - Ask staff for your invoice
   - View payment history
   - See remaining balance

### For Staff

**Daily Tasks:**

1. **Process Returns**
   - Return books normally
   - Invoices auto-generated if fees apply
   - Note invoice number for borrower

2. **Accept Payments**
   - Navigate to Finance → Invoices
   - Find borrower's invoice
   - Record payment with method
   - Provide receipt (invoice number)

3. **Handle Disputes**
   - Review invoice details
   - Check fee calculation
   - Waive if justified
   - Document reason

4. **Monitor Overdue**
   - Check "Overdue" tab daily
   - Contact borrowers
   - Escalate if necessary

---

## Best Practices

### For Staff

1. **Always Verify Fees**
   - Check calculated fees before finalizing return
   - Explain fees to borrower
   - Provide invoice number

2. **Accurate Payment Recording**
   - Enter exact amount received
   - Select correct payment method
   - Add notes for check numbers, transaction IDs

3. **Document Waivers**
   - Provide detailed reason
   - Get approval if required
   - Maintain consistency

4. **Follow Up on Overdue**
   - Contact borrowers promptly
   - Offer payment plans if needed
   - Escalate persistent non-payment

### For Administrators

1. **Regular Monitoring**
   - Review dashboard weekly
   - Track overdue trends
   - Identify problem areas

2. **Fee Settings Review**
   - Adjust based on return rates
   - Balance revenue vs. member satisfaction
   - Document changes

3. **Payment Plans**
   - Create guidelines for partial payments
   - Set minimum payment amounts
   - Define maximum payment period

4. **Reporting**
   - Monthly financial reports
   - Outstanding balance tracking
   - Collection rate metrics

---

## Technical Details

### Database Schema

**Invoices Table:**
```sql
- id (primary key)
- invoice_number (unique, auto-generated)
- transaction_id (foreign key → transactions)
- user_id (foreign key → users)
- overdue_fee (integer, cents)
- lost_fee (integer, cents)
- damage_fee (integer, cents)
- total_amount (integer, cents)
- amount_paid (integer, cents)
- amount_due (integer, cents)
- status (enum: unpaid, partially_paid, paid, waived)
- invoice_date (date)
- due_date (date)
- paid_at (timestamp, nullable)
- notes (text, nullable)
- created_at, updated_at
```

### Service Layer

**InvoiceService Methods:**

```php
// Generate invoice from transaction
generateInvoiceForTransaction(Transaction $transaction, int $paymentDueDays = 30)

// Record a payment
recordPayment(Invoice $invoice, float $amount, ?string $paymentMethod, ?string $notes)

// Waive invoice
waiveInvoice(Invoice $invoice, ?string $reason)

// Get user's unpaid invoices
getUnpaidInvoices(User $user)

// Get user's overdue invoices
getOverdueInvoices(User $user)

// Get user's outstanding balance
getUserOutstandingBalance(User $user)

// Get invoice summary for user
getUserInvoiceSummary(User $user)

// Get invoice data for PDF/printing
getInvoiceData(Invoice $invoice)
```

### Integration with Transactions

**Automatic Generation:**

In `TransactionService::returnTransaction()`:

```php
// After processing return
$invoice = $this->invoiceService->generateInvoiceForTransaction($transaction);

if ($invoice) {
    Log::info("Invoice generated", [
        'invoice_number' => $invoice->invoice_number,
        'total_amount' => $invoice->formatted_total_amount
    ]);
}
```

### Model Relationships

```php
// Transaction → Invoice
$transaction->invoice; // HasOne

// User → Invoices
$user->invoices; // HasMany (need to add to User model)

// Invoice → Transaction
$invoice->transaction; // BelongsTo

// Invoice → User
$invoice->user; // BelongsTo
```

---

## Troubleshooting

### Invoice Not Created

**Issue:** Returned book but no invoice generated

**Possible Causes:**
1. Total fees = $0 (no invoice needed)
2. Transaction not marked as returned
3. System error during return process

**Solution:**
- Verify fees were calculated
- Check transaction status
- Review system logs
- Manually create invoice if needed

### Payment Not Recording

**Issue:** Payment button not working

**Possible Causes:**
1. Invoice already paid
2. Invoice waived
3. Amount exceeds amount due

**Solution:**
- Check invoice status
- Verify amount entered
- Ensure invoice is unpaid or partially paid

### Incorrect Fee Amount

**Issue:** Fee amount doesn't match expectation

**Possible Causes:**
1. Grace period applied
2. Fee cap applied
3. Settings changed recently

**Solution:**
- Review Fee Management settings
- Check grace period configuration
- Verify max day/amount caps
- Review calculation in transaction items

---

## Future Enhancements

Planned features for future releases:

### Phase 2
- [ ] PDF invoice generation
- [ ] Print invoice functionality
- [ ] Email invoices to borrowers
- [ ] SMS notifications for overdue

### Phase 3
- [ ] Online payment portal for members
- [ ] Payment gateway integration
- [ ] Automatic payment reminders
- [ ] Recurring payment plans

### Phase 4
- [ ] Advanced reporting dashboard
- [ ] Revenue forecasting
- [ ] Collection analytics
- [ ] Export to accounting software

### Phase 5
- [ ] Member payment history
- [ ] Invoice dispute system
- [ ] Bulk waiver processing
- [ ] Custom invoice templates

---

## API Examples

### Generate Invoice Manually

```php
use App\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);

// Generate for a returned transaction
$invoice = $invoiceService->generateInvoiceForTransaction(
    $transaction,
    30 // Payment due in 30 days
);

if ($invoice) {
    echo "Invoice created: " . $invoice->invoice_number;
} else {
    echo "No fees to invoice";
}
```

### Record Payment

```php
use App\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);

$invoice = Invoice::find(1);

// Record full payment
$invoiceService->recordPayment(
    $invoice,
    25.50,              // Amount in dollars
    'cash',             // Payment method
    'Paid in full'      // Optional notes
);

// Record partial payment
$invoiceService->recordPayment(
    $invoice,
    10.00,
    'card',
    'First installment - Card ending 1234'
);
```

### Waive Invoice

```php
use App\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);

$invoice = Invoice::find(1);

$invoiceService->waiveInvoice(
    $invoice,
    'First-time offender, waived as courtesy'
);
```

### Get User's Outstanding Balance

```php
use App\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);

$user = User::find(1);

$balance = $invoiceService->getUserOutstandingBalance($user);
echo "Outstanding: $" . number_format($balance, 2);

// Get detailed summary
$summary = $invoiceService->getUserInvoiceSummary($user);
/*
Returns:
[
    'unpaid_count' => 2,
    'partially_paid_count' => 1,
    'overdue_count' => 1,
    'outstanding_balance' => 45.50,
    'formatted_balance' => '$45.50',
    'has_overdue' => true
]
*/
```

### Get Invoice Data for Display

```php
use App\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);

$invoice = Invoice::find(1);

$data = $invoiceService->getInvoiceData($invoice);

// Use for PDF generation, email templates, etc.
echo $data['invoice_number'];
echo $data['borrower']['name'];
echo $data['fees']['total'];
```

---

## Integration Points

### With Transaction System

- Invoices created automatically on return
- Transaction reference linked
- Fee breakdown preserved
- Status synchronized

### With Fee Management

- Overdue fees use configured rates
- Lost book fees follow settings
- Grace periods applied
- Caps enforced

### With Membership System

- User relationship maintained
- Membership type displayed
- Outstanding balance tracked
- Payment history linked

### With User Management

- Borrower information pulled
- Email for notifications (future)
- Payment preferences (future)
- Account restrictions based on outstanding balance (future)

---

## Frequently Asked Questions

**Q: When is an invoice created?**  
A: Automatically when a transaction is returned and has fees > $0.

**Q: Can invoices be deleted?**  
A: Only by administrators. Deleting removes all payment history.

**Q: Can a paid invoice be modified?**  
A: No. Paid invoices are locked. Contact an administrator if corrections are needed.

**Q: What if a borrower disputes an invoice?**  
A: Staff can review the linked transaction, check fee calculations, and waive if justified.

**Q: How long do borrowers have to pay?**  
A: Default is 30 days from invoice date. Configurable per invoice.

**Q: Can partial payments be made?**  
A: Yes. Multiple partial payments are supported until invoice is fully paid.

**Q: What happens if payment is late?**  
A: Invoice status shows "Overdue" but no additional fees. Library policy applies.

**Q: Can invoices be consolidated?**  
A: Not currently. Each transaction generates a separate invoice.

**Q: Is there a payment plan option?**  
A: Staff can accept partial payments informally. Formal payment plans are planned for future.

**Q: How do I generate a receipt?**  
A: Invoice number serves as receipt. PDF receipts coming in future update.

---

## Support

### For Issues

1. Check this documentation
2. Review TRANSACTION_FLOW_V2.md
3. Check FEE_MANAGEMENT.md
4. Contact system administrator

### For Feature Requests

Submit requests to development team with:
- Use case description
- Business justification
- Priority level
- Example scenarios

---

**Last Updated:** December 16, 2025  
**Version:** 1.0  
**Status:** Production Ready  
**Author:** Library System Development Team