# Transaction Status Flow - Improved Implementation

## Overview

This document explains the improved transaction status management system that prevents staff from arbitrarily changing transaction statuses and enforces proper business logic.

## Problem Statement

**Before:** Staff could freely change transaction status between any states (Borrowed → Returned → Borrowed → Delayed, etc.), which:
- Violated business logic
- Could corrupt fee calculations
- Allowed reverting finalized transactions
- Made audit trails unreliable

**After:** Transaction status is automatically determined based on business rules, and finalized transactions cannot be reversed.

---

## Status Types

### 1. **Borrowed** (Active State)
- **Description**: Book is currently borrowed
- **Characteristics**:
  - Transaction is active
  - No return date set
  - Books are marked as unavailable
  - Can be renewed (if within limits)
- **Allowed Transitions**: → Returned, Delayed, Lost, Damaged

### 2. **Returned** (Final State)
- **Description**: Book returned on time
- **Characteristics**:
  - Return date ≤ Due date
  - No overdue fine
  - Books marked as available again
  - **Cannot be changed back to Borrowed**
  - **Cannot be manually selected** - always auto-determined
- **Auto-determined by**: Setting return date on/before due date when status is "Borrowed"

### 3. **Delayed** (Final State)
- **Description**: Book returned late
- **Characteristics**:
  - Return date > Due date
  - Overdue fines calculated
  - Books marked as available again
  - **Cannot be changed back to Borrowed**
  - **Cannot be manually selected** - always auto-determined
- **Auto-determined by**: Setting return date after due date when status is "Borrowed"

### 4. **Lost** (Final State)
- **Description**: Book declared lost
- **Characteristics**:
  - Lost book fine applied
  - Book may be marked as unavailable/lost in inventory
  - **Cannot be changed back to Borrowed**
  - **Manually selectable** by staff
- **Manually set by**: Staff selecting "Lost" from status dropdown

### 5. **Damaged** (Final State)
- **Description**: Book returned with damage
- **Characteristics**:
  - Damage fine applied
  - Damage notes recorded
  - **Cannot be changed back to Borrowed**
  - **Manually selectable** by staff
- **Manually set by**: Staff selecting "Damaged" from status dropdown

---

## Status Flow Rules

### For Active (Borrowed) Transactions

```
┌─────────────┐
│  BORROWED   │ ← Initial state when transaction created
└─────┬───────┘
      │
      ├─── Set return date ON TIME ──→ RETURNED (Auto)
      │
      ├─── Set return date LATE ──→ DELAYED (Auto)
      │
      ├─── Mark as lost ──→ LOST (Manual)
      │
      └─── Mark as damaged ──→ DAMAGED (Manual)
```

### For Finalized Transactions

```
┌─────────────┐
│  RETURNED   │ ──✗── Cannot change back (Auto-determined only)
└─────────────┘

┌─────────────┐
│   DELAYED   │ ──✗── Cannot change back (Auto-determined only)
└─────────────┘

┌─────────────┐
│    LOST     │ ──✗── Cannot change back (Manually selectable)
└─────────────┘

┌─────────────┐
│   DAMAGED   │ ──✗── Cannot change back (Manually selectable)
└─────────────┘
```

---

## Staff Actions & UI Behavior

### Creating a Transaction
- Status is automatically set to **Borrowed**
- Staff cannot change the status during creation
- Only "Borrowed" option is available in dropdown

### Editing an Active (Borrowed) Transaction

**Staff Can:**
- View current transaction details
- Set the return date (which auto-determines Returned/Delayed status)
- Manually select "Lost" from status dropdown
- Manually select "Damaged" from status dropdown
- Update borrowed duration (before finalization)

**Status Options Available:**
- **Borrowed** (current state)
- **Lost** (manual selection only)
- **Damaged** (manual selection only)
- ~~Returned~~ (NOT selectable - auto-determined only)
- ~~Delayed~~ (NOT selectable - auto-determined only)

**UI Behavior:**
- Return date field is visible and editable
- Helper text shows: "💡 Tip: Set return date to auto-determine Returned/Delayed status. Manually select Lost or Damaged if applicable."
- When return date is entered (and status is Borrowed), helper shows:
  - ✓ On Time - Status will be: RETURNED | Fine: $0.00
  - ⚠️ Late - Status will be: DELAYED | Fine: $X.XX
- If status manually set to Lost/Damaged, helper shows:
  - ❌ Status: LOST (manually set) | Fine: $X.XX
  - ⚠️ Status: DAMAGED (manually set) | Fine: $X.XX
- Status automatically updates to Returned/Delayed when return date is saved (if not Lost/Damaged)

### Editing a Finalized Transaction

**Staff Cannot:**
- Change the status back to Borrowed
- Modify the return date
- Change core transaction details

**Staff Can:**
- View transaction details
- Update fine amounts (if needed for corrections)
- Add notes to transaction items

**UI Behavior:**
- Status field is **disabled** and shows only current status
- Helper text shows: "⚠️ This transaction is finalized. Status cannot be changed."
- Finalized info displayed: "✓ This transaction was finalized as **Returned** on Jan 15, 2024"
- Delete action is disabled

---

## Automatic Fee Calculation

### On-Time Return (Status: Returned)
```
Return Date: Jan 15, 2024
Due Date: Jan 20, 2024
Status: RETURNED (Auto)
Fine: $0.00
```

### Late Return (Status: Delayed)
```
Return Date: Jan 25, 2024
Due Date: Jan 20, 2024
Days Late: 5 days
Overdue Fee: $5.00 ($1/day)
Status: DELAYED (Auto)
Fine: $5.00
```

### Lost Book (Status: Lost)
```
Book marked as: Lost
Lost Book Fine: $25.00 (or % of book price)
Status: LOST
Fine: $25.00
```

### Damaged Book (Status: Damaged)
```
Book marked as: Damaged
Damage Fine: $10.00 (staff-entered)
Status: DAMAGED
Fine: $10.00
```

---

## Implementation Details

### Code Locations

**Resource Definition:**
- `app/Filament/Staff/Resources/TransactionResource.php`
  - Status field with dynamic options
  - Auto-determination logic in `afterStateUpdated`
  - Disabled state for finalized transactions

**Edit Page:**
- `app/Filament/Staff/Resources/TransactionResource/Pages/EditTransaction.php`
  - `mutateFormDataBeforeSave()` - Validates and auto-determines status
  - `afterSave()` - Updates fines and sends notifications
  - Delete action disabled for finalized transactions

**Model:**
- `app/Models/Transaction.php`
  - Status casting to BorrowedStatus enum
  - Fine calculation methods
  - Observer for automatic updates

### Key Methods

```php
// Auto-determine status in EditTransaction
protected function mutateFormDataBeforeSave(array $data): array
{
    if (isset($data['returned_date']) && $data['returned_date']) {
        $returnDate = Carbon::parse($data['returned_date']);
        $dueDate = $this->record->due_date;
        
        if ($returnDate->lte($dueDate)) {
            $data['status'] = BorrowedStatus::Returned->value;
        } else {
            $data['status'] = BorrowedStatus::Delayed->value;
        }
    }
    
    return $data;
}
```

---

## Validation Rules

1. **Cannot revert finalized transactions**
   - Once status is Returned/Delayed/Lost/Damaged, it's final
   - UI prevents changing the status field
   - Backend validation blocks status manipulation

2. **Automatic status determination (Returned/Delayed only)**
   - Returned and Delayed statuses are NEVER manually selectable
   - These are always auto-determined by return date vs due date
   - Lost and Damaged must be manually selected by staff
   - Return date logic only applies when status is Borrowed (not Lost/Damaged)

3. **Manual status selection (Lost/Damaged only)**
   - Staff can manually select "Lost" or "Damaged" from dropdown
   - Once selected, auto-determination is skipped
   - These become final states immediately

4. **Delete restrictions**
   - Cannot delete finalized transactions
   - Only active (Borrowed) transactions can be deleted

5. **Edit restrictions**
   - Finalized transactions have limited edit capability
   - Core fields (dates, status, user) are locked
   - Only fine adjustments and notes can be updated

---

## Benefits

### ✅ Data Integrity
- Transaction history is reliable and cannot be manipulated
- Audit trails are accurate
- Fee calculations are consistent

### ✅ Business Logic Enforcement
- Status transitions follow proper workflow
- No arbitrary status changes
- Automatic determination prevents human error

### ✅ Better User Experience
- Clear feedback on what will happen
- No confusion about which status to select
- Helper text guides staff through the process

### ✅ Security
- Prevents fraudulent transaction modifications
- Finalized transactions are immutable
- Clear separation between active and completed transactions

---

## Migration Guide

If you have existing transactions with incorrect status flows:

1. **Audit existing data:**
```sql
-- Find transactions marked as Borrowed but have return dates
SELECT * FROM transactions 
WHERE status = 'borrowed' 
AND returned_date IS NOT NULL;

-- Fix them
UPDATE transactions 
SET status = CASE 
    WHEN returned_date <= due_date THEN 'returned'
    ELSE 'delayed'
END
WHERE status = 'borrowed' 
AND returned_date IS NOT NULL;
```

2. **Verify finalized transactions:**
```sql
-- Ensure all finalized transactions have return dates
SELECT * FROM transactions 
WHERE status IN ('returned', 'delayed', 'lost', 'damaged')
AND returned_date IS NULL;
```

---

## Future Enhancements

### Potential Additions:
1. **Partial Returns**: Handle transactions with multiple books where some are returned and others are not
2. **Status History**: Log all status changes with timestamps
3. **Dispute Resolution**: Allow admin to override finalized status with approval workflow
4. **Payment Integration**: Link payment records to finalized transactions with fines
5. **Automated Reminders**: Send notifications when transactions become overdue

---

## FAQ

**Q: Can staff ever change a finalized transaction?**
A: No. Once a transaction is finalized (Returned/Delayed/Lost/Damaged), the status cannot be changed. This ensures data integrity.

**Q: Why can't staff manually select "Returned" or "Delayed"?**
A: These statuses are always auto-determined by the system based on the return date vs due date. This eliminates human error and ensures consistency. Staff can only manually select Borrowed, Lost, or Damaged.

**Q: What if staff makes a mistake when setting the return date?**
A: If the transaction was just finalized, an admin can manually adjust it in the database. For production systems, implement an approval workflow for corrections.

**Q: How do we handle books that are partially damaged vs totally lost?**
A: Use the "Damaged" status for repairable damage with a fine. Use "Lost" for books that won't be returned.

**Q: Can a transaction be both Delayed and Damaged?**
A: If you manually select "Damaged", it will stay as Damaged (overdue fees still calculated). If you want it marked as Delayed, keep status as "Borrowed" and just set the return date - the system will auto-determine "Delayed" if late.

**Q: What happens if I set return date AND manually select Lost?**
A: The manual Lost status takes priority. The auto-determination only works when status is "Borrowed". Lost and Damaged selections override the automatic behavior.

**Q: What happens to book availability when status changes?**
A: The TransactionObserver handles this automatically:
- Borrowed: Books marked unavailable
- Returned/Delayed: Books marked available
- Lost: Books may be removed from circulation
- Damaged: Books reviewed for availability

---

## Support

For questions or issues with transaction status management:
1. Check this documentation
2. Review the code in `TransactionResource.php` and `EditTransaction.php`
3. Check the `BorrowedStatus` enum for status definitions
4. Review `TransactionObserver.php` for automatic behaviors

---

**Last Updated:** January 2024  
**Version:** 2.0  
**Author:** Library System Development Team