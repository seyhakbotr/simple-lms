# Staff Quick Guide: Transaction Management

## 🎯 Quick Reference for Staff Members

This guide shows you how to handle book transactions with the improved status system.

---

## Creating a New Transaction (Borrowing Books)

### Steps:
1. Click **"New Transaction"**
2. Select the **Borrower** from dropdown
3. Set the **Borrowed Date** (defaults to today)
4. Add **Books to Borrow**:
   - Click "Add item" to add more books
   - Select book title
   - Set borrowing duration (days)
5. Click **"Create"**

### What Happens:
- ✅ Status is automatically set to **Borrowed**
- ✅ Books marked as unavailable
- ✅ Due date calculated automatically
- ✅ Borrower receives notification

**Note:** You cannot change the status during creation - it's always "Borrowed"

---

## Returning Books (On Time)

### Scenario: Book returned before/on the due date

### Steps:
1. Click on the transaction to edit
2. Set the **Returned Date** (today's date or actual return date)
3. Watch the helper text - it will show:
   ```
   ✓ On Time - Status will be: RETURNED | ✓ No fine
   ```
4. Click **"Save"**

### What Happens:
- ✅ Status automatically changes to **Returned**
- ✅ Books marked as available
- ✅ No fine charged
- ✅ Transaction is finalized (cannot be changed)

---

## Returning Books (Late)

### Scenario: Book returned after the due date

### Steps:
1. Click on the transaction to edit
2. Set the **Returned Date** (actual return date)
3. Watch the helper text - it will show:
   ```
   ⚠️ Late - Status will be: DELAYED | 💰 Fine: $5.00
   ```
4. Review the fine amount
5. Click **"Save"**

### What Happens:
- ✅ Status automatically changes to **Delayed**
- ✅ Overdue fine calculated automatically
- ✅ Books marked as available
- ✅ Admin receives notification of the delay
- ✅ Transaction is finalized (cannot be changed)

### Fine Calculation:
- Days Late = Return Date - Due Date
- Fine = Days Late × Fee Per Day (see Fee Settings)
- Grace period applied automatically (if configured)

**Example:**
```
Borrowed: Jan 10, 2024
Due Date: Jan 20, 2024
Returned: Jan 25, 2024
Days Late: 5 days
Fine: $5.00 ($1.00/day × 5 days)
Status: DELAYED (automatic)
```

---

## Marking Books as Lost

### Scenario: Borrower claims book is lost

### Steps:
1. Click on the transaction to edit
2. Change **Status** to **Lost**
3. Review the lost book fine (calculated automatically)
4. Set **Returned Date** (optional - date book was reported lost)
5. Click **"Save"**

### What Happens:
- ✅ Status set to **Lost**
- ✅ Lost book fine applied (based on book price or fixed amount)
- ✅ Book may be marked as lost in inventory
- ✅ Transaction is finalized (cannot be changed)

### Fine Types:
- **Percentage of Book Price**: e.g., 100% of book's value
- **Fixed Amount**: e.g., $25.00 per lost book

---

## Marking Books as Damaged

### Scenario: Book returned with damage

### Steps:
1. Click on the transaction to edit
2. Change **Status** to **Damaged**
3. Enter **Damage Fine** amount
4. Add **Damage Notes** (describe the damage)
5. Set **Returned Date**
6. Click **"Save"**

### What Happens:
- ✅ Status set to **Damaged**
- ✅ Custom damage fine applied
- ✅ Damage notes recorded
- ✅ Book reviewed for continued circulation
- ✅ Transaction is finalized (cannot be changed)

**Damage Fine Guidelines:**
- Minor (torn page, bent cover): $2-5
- Moderate (multiple pages, water damage): $5-15
- Severe (unusable sections): $15-25

---

## Understanding Transaction Status

### 🟦 BORROWED (Active)
- Book is currently out
- Can be edited
- Can set return date
- Can renew (if allowed)

### 🟢 RETURNED (Finalized)
- Book returned on time
- No fine
- **Cannot be edited**
- **Cannot be deleted**

### 🟡 DELAYED (Finalized)
- Book returned late
- Overdue fine charged
- **Cannot be edited**
- **Cannot be deleted**

### 🔴 LOST (Finalized)
- Book declared lost
- Lost book fine charged
- **Cannot be edited**
- **Cannot be deleted**

### 🟠 DAMAGED (Finalized)
- Book returned damaged
- Damage fine charged
- **Cannot be edited**
- **Cannot be deleted**

---

## Important Rules

### ✅ DO:
- Set accurate return dates
- Review fine calculations before saving
- Add detailed notes for damaged books
- Communicate with borrowers about fines

### ❌ DON'T:
- Try to change finalized transactions
- Guess at damage fine amounts - follow guidelines
- Delete transactions with fines
- Change return dates after saving (they're final!)

---

## Status Changes You'll See

### When You Set Return Date:
```
Before: Status = Borrowed
Action: Set return date to Jan 15 (due date was Jan 20)
Result: Status automatically changes to RETURNED
```

```
Before: Status = Borrowed
Action: Set return date to Jan 25 (due date was Jan 20)
Result: Status automatically changes to DELAYED
Fine: Automatically calculated
```

### What You CANNOT Do:
```
❌ Change RETURNED back to BORROWED
❌ Change DELAYED back to BORROWED
❌ Change LOST back to BORROWED
❌ Change DAMAGED back to BORROWED
❌ Delete finalized transactions
❌ Modify return dates after saving
```

---

## Fee Breakdown Display

When you edit a transaction, you'll see:

### Active Transaction (Borrowed):
```
📅 Set the return date to finalize this transaction

Status: Borrowed
No fines yet
```

### With Return Date Set (Before Saving):
```
✓ On Time - Status will be: RETURNED | ✓ No fine

OR

⚠️ Late - Status will be: DELAYED | 💰 Fine: $8.00
```

### Finalized Transaction:
```
✓ This transaction was finalized as Returned on Jan 15, 2024

Fee Breakdown:
✓ No fines

OR

Fee Breakdown:
Overdue: $5.00
Damage: $3.00
Total: $8.00
```

---

## Troubleshooting

### "I can't change the status!"
- **Reason:** Transaction is finalized (Returned/Delayed/Lost/Damaged)
- **Solution:** Finalized transactions cannot be changed. Contact admin if correction needed.

### "The return date field is missing!"
- **Reason:** Transaction is already finalized
- **Solution:** Return date has already been set and saved. View-only now.

### "The fine seems wrong!"
- **Reason:** Check grace period and fee settings
- **Action:** Review fee breakdown, verify return date, check Fee Settings page

### "I set the wrong return date!"
- **If not saved yet:** Just change it before clicking Save
- **If already saved:** Transaction is finalized. Contact admin for corrections.

### "Can I delete this transaction?"
- **Active (Borrowed):** Yes, delete button available
- **Finalized:** No, delete button is disabled

---

## Common Scenarios

### Scenario 1: Same-Day Return
```
Action: Borrow and return on the same day
Steps:
1. Create transaction (Status: Borrowed)
2. Immediately edit and set return date to today
3. Status auto-changes to RETURNED
4. No fine
```

### Scenario 2: Partial Damage
```
Situation: Book has water stain on one page
Steps:
1. Edit transaction
2. Set Status to DAMAGED
3. Enter fine: $3.00
4. Notes: "Water stain on page 45, still readable"
5. Set return date and save
```

### Scenario 3: Overdue by Many Days
```
Situation: Book is 30 days overdue
Steps:
1. Edit transaction
2. Set return date (30 days after due date)
3. System shows: "⚠️ Late - Status will be: DELAYED | 💰 Fine: $30.00"
4. Verify fine amount
5. Save (Status auto-set to DELAYED)
```

### Scenario 4: Lost Book After Being Overdue
```
Situation: Book was overdue, now lost
Steps:
1. Transaction currently shows DELAYED with overdue fine
2. Cannot change to LOST (transaction already finalized)
3. Contact admin to handle lost book separately
```

---

## Tips for Efficiency

### ⚡ Quick Tips:
1. **Use keyboard**: Tab through fields quickly
2. **Check helper text**: It tells you what will happen before you save
3. **Verify borrower info**: Shows current borrowed count and limits
4. **Review before saving**: Once finalized, you can't change it
5. **Add notes**: Especially for damaged or lost books

### 📊 At a Glance:
- **Green checkmark (✓)**: Good to go, no issues
- **Yellow warning (⚠️)**: Late return, fine will be charged
- **Red X (❌)**: Lost or severely damaged
- **Blue info (💡)**: Helpful tips and information

---

## Need Help?

### Questions About:
- **Fines**: Check Fee Settings or ask admin
- **Membership limits**: View user's membership type details
- **Book availability**: Check Books page
- **Transaction history**: Use filters on Transactions list
- **Corrections needed**: Contact admin for finalized transaction changes

---

## Summary

### The New Flow is Simple:
1. **Create** transaction (Status: Borrowed)
2. **Set return date** when book comes back
3. **System auto-determines** status (Returned or Delayed)
4. **Transaction finalizes** automatically
5. **No manual status changes** needed!

### Remember:
- ✅ Status is **automatic** based on return date
- ✅ Fines are **calculated** for you
- ✅ Finalized transactions are **permanent**
- ✅ Helper text **guides** you every step

---

**Questions?** Contact your system administrator or refer to the full documentation.

**Last Updated:** January 2024