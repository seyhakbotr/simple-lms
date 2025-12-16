# Invoice System - Quick Start Guide

## 📋 Overview

The Invoice System automatically generates invoices when books are returned with fees. Staff can view, manage payments, and waive invoices through the Admin panel.

---

## 🚀 Quick Start

### For Staff: Processing Returns with Fees

1. **Return Books Normally**
   - Go to Transactions → [Select Transaction] → Return Books
   - Mark lost/damaged books if applicable
   - Review fee preview
   - Click "Process Return"

2. **Invoice Auto-Generated**
   - If fees > $0, invoice is created automatically
   - Note the invoice number (format: `INV-20251216-0001`)
   - Inform borrower of:
     - Invoice number
     - Total amount due
     - Due date (30 days from return)
     - Payment methods accepted

3. **Accept Payment**
   - Go to Finance → Invoices
   - Search for invoice number or borrower name
   - Click "Record Payment"
   - Enter amount and payment method
   - Submit

---

## 💰 Recording Payments

### Quick Payment

**From Invoice List:**
```
1. Finance → Invoices
2. Find invoice (search or filter)
3. Click "Record Payment" action (💵 icon)
4. Fill form:
   - Amount: $XX.XX
   - Method: Cash/Card/Check/etc.
   - Notes: (optional)
5. Submit
```

### Payment Example

```
Invoice: INV-20251216-0001
Amount Due: $25.00

Payment 1:
- Amount: $10.00
- Method: Cash
→ Status: Partially Paid, Due: $15.00

Payment 2:
- Amount: $15.00
- Method: Card
→ Status: Paid, Due: $0.00
```

---

## 🔍 Finding Invoices

### Quick Search

**By Invoice Number:**
- Type `INV-20251216-0001` in search box

**By Borrower:**
- Type borrower's name in search box

**By Status:**
- Use tabs: Unpaid | Partially Paid | Overdue | Paid | Waived

### Common Filters

**Overdue Only:**
1. Click "Overdue" tab
2. Shows invoices past due date

**This Month:**
1. Click filter icon
2. Select "Invoice Date" filter
3. Set date range

---

## ❌ Waiving Invoices

**When to Waive:**
- First-time minor infraction
- System error
- Special circumstances
- Goodwill gesture

**How to Waive:**
```
1. Open invoice
2. Click "Waive Invoice" button
3. Enter reason (required):
   "First-time offender, waived as courtesy"
4. Confirm
→ Status: Waived, Due: $0.00
```

⚠️ **Important:**
- Waiving is permanent
- Requires authorization
- Reason is logged
- Cannot waive paid invoices

---

## 📊 Dashboard Widgets

When you open Finance → Invoices, you'll see:

| Widget | Meaning |
|--------|---------|
| **Total Outstanding** | Total unpaid amount across all invoices |
| **Total Collected** | All-time revenue from fees |
| **Overdue Invoices** | Count of past-due invoices (needs attention) |
| **This Month** | Invoices generated this month |
| **Month Revenue** | Payments received this month |

---

## 📄 Invoice Statuses

| Status | Badge Color | Meaning | Can Pay? |
|--------|-------------|---------|----------|
| **Unpaid** | 🔴 Red | No payment received | ✅ Yes |
| **Partially Paid** | 🟡 Yellow | Some payment received | ✅ Yes |
| **Paid** | 🟢 Green | Fully paid | ❌ No |
| **Waived** | ⚪ Gray | Fees forgiven | ❌ No |

---

## 🎯 Common Tasks

### Task 1: Accept Payment at Desk

```
Borrower: "I want to pay my fine"

1. Ask for invoice number or name
2. Finance → Invoices → Search
3. Click "Record Payment"
4. Enter amount received
5. Select payment method
6. Submit
7. Tell borrower: "Payment recorded, your balance is now $X.XX"
```

### Task 2: Check Borrower's Outstanding Balance

```
1. Finance → Invoices
2. Search borrower's name
3. Look at "Amount Due" column
4. Add up unpaid/partially paid invoices
```

**Tip:** Click on borrower's name to see all their invoices

### Task 3: Handle Dispute

```
Borrower: "This fee is wrong!"

1. Find invoice
2. Click to view details
3. Review "Transaction Details" section
4. Check fee breakdown
5. Verify calculation with Fee Management settings
6. If justified, click "Waive Invoice"
7. If correct, explain calculation to borrower
```

### Task 4: Follow Up on Overdue

```
Daily task:
1. Finance → Invoices → Overdue tab
2. Note count and amounts
3. Contact borrowers with overdue invoices
4. Offer payment options
5. Record any payments received
```

---

## 💡 Tips & Tricks

### Partial Payments

✅ **Always Accepted**
- No minimum amount required
- Multiple payments allowed
- Status auto-updates

```
Example:
Invoice: $50.00

Week 1: Pay $20 → Due: $30 (Partially Paid)
Week 2: Pay $20 → Due: $10 (Partially Paid)
Week 3: Pay $10 → Due: $0 (Paid)
```

### Payment Methods

Available options:
- 💵 Cash
- 💳 Credit/Debit Card
- 📝 Check (note check number in notes)
- 🏦 Bank Transfer
- 🌐 Online Payment

**Pro Tip:** For checks, add check number to notes:
```
Notes: "Check #12345"
```

### Quick Navigation

- Invoice → Transaction: Click transaction reference
- Transaction → Invoice: View transaction, scroll to invoice section
- Invoice → Borrower: Click borrower name

---

## 🔧 Troubleshooting

### Invoice Not Found

**Problem:** Can't find invoice after return

**Solution:**
1. Check if fees were actually charged (view transaction)
2. Search by transaction reference
3. Check if invoice was auto-generated (review logs)

### Payment Not Recording

**Problem:** Can't record payment

**Check:**
- Is invoice already paid? ✅
- Is invoice waived? ✅
- Is amount > amount due? ✅
- Are you entering valid amount? ✅

### Wrong Fee Amount

**Problem:** Fee doesn't seem right

**Check:**
1. Settings → Fee Management
2. Verify:
   - Overdue fee per day
   - Grace period
   - Maximum caps
3. Review transaction item fees
4. Contact admin if settings seem wrong

---

## 📱 For Borrowers

### What Borrowers Need to Know

**When You Get an Invoice:**
- Invoice number: `INV-XXXXXXXX-XXXX`
- Total amount due
- Payment due date (usually 30 days)
- How to pay (at library desk)

**Payment Options:**
- Full payment (pay entire amount)
- Partial payment (pay what you can now)
- Multiple payments accepted

**Important:**
- Keep invoice number for reference
- Pay before due date when possible
- Contact library if you have questions
- Partial payments prevent "overdue" status

---

## 📈 Reports (Admin)

### Monthly Summary

View on invoice list page:
- Total outstanding this month
- Total collected this month
- Number of overdue invoices
- Trends over last 6 months

### Export Data

**Coming Soon:**
- PDF invoice generation
- Excel export
- Monthly reports
- Revenue analysis

---

## 🎓 Training Scenarios

### Scenario 1: Simple Payment

```
Borrower returns book 3 days late
Fee: $7.50

1. Staff processes return
2. Invoice INV-20251216-0001 created
3. Borrower pays $7.50 cash
4. Staff records payment
5. Invoice marked "Paid"
```

### Scenario 2: Partial Payments

```
Borrower returns book very late
Fee: $50.00

Visit 1:
- Borrower: "Can I pay $20 today?"
- Staff: "Yes, partial payments accepted"
- Records $20 payment
- Status: Partially Paid, Due: $30

Visit 2 (2 weeks later):
- Borrower pays remaining $30
- Status: Paid
```

### Scenario 3: Lost Book

```
Borrower never returned book
Fee: $45.00 (lost book replacement)

1. Staff marks book as lost during return
2. Invoice created: $45.00
3. Borrower: "I'll pay $15 per week"
4. Week 1: Pay $15 → Due $30
5. Week 2: Pay $15 → Due $15
6. Week 3: Pay $15 → Paid
```

### Scenario 4: Waiving Fee

```
First-time borrower, 1 day late
Fee: $2.50

Staff decision: Waive as courtesy
1. Open invoice
2. Click "Waive Invoice"
3. Reason: "First-time borrower, one-day grace"
4. Confirm
5. Invoice waived
```

---

## ✅ Best Practices

### DO ✅

- ✅ Inform borrowers of invoice number
- ✅ Explain fee calculation clearly
- ✅ Accept partial payments
- ✅ Add notes to payment records
- ✅ Check overdue tab daily
- ✅ Document waiver reasons thoroughly
- ✅ Verify payment amount before submitting

### DON'T ❌

- ❌ Delete paid invoices
- ❌ Waive without valid reason
- ❌ Forget to record payment method
- ❌ Round amounts (use exact change)
- ❌ Ignore overdue invoices
- ❌ Promise waivers without authorization

---

## 📞 Need Help?

**For Issues:**
1. Check this guide
2. Review INVOICE_SYSTEM.md (full documentation)
3. Check TRANSACTION_FLOW_V2.md
4. Contact system administrator

**For Questions:**
- How fees are calculated → FEE_MANAGEMENT.md
- How returns work → TRANSACTION_FLOW_V2.md
- General system → README.md

---

## 🔗 Related Documentation

- **INVOICE_SYSTEM.md** - Complete documentation
- **TRANSACTION_FLOW_V2.md** - Return process
- **FEE_MANAGEMENT.md** - Fee configuration
- **MEMBERSHIP_TYPE_INTEGRATION.md** - Member settings

---

## 🆕 Recent Updates

**Version 1.0 (Dec 16, 2025)**
- ✨ Initial invoice system release
- ✨ Automatic invoice generation
- ✨ Payment recording
- ✨ Invoice waiving
- ✨ Statistics dashboard

**Coming Soon:**
- 📄 PDF invoice generation
- 📧 Email notifications
- 💳 Online payment portal
- 📊 Advanced reporting

---

**Quick Reference:** Keep this guide handy at the desk for fast lookups!

**Last Updated:** December 16, 2025  
**Version:** 1.0