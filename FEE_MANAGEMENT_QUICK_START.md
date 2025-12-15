# Fee Management Quick Start Guide

## Quick Access

**Admin Panel:** Settings → Fee Management

## Default Settings (Out of the Box)

| Setting | Default Value |
|---------|---------------|
| Overdue Fee Per Day | $10.00 |
| Grace Period | 0 days |
| Lost Book Fine | 100% of book price |
| Currency | USD ($) |
| Partial Payments | Enabled |
| Overdue Notifications | Enabled |

## Common Configuration Tasks

### 1. Change Overdue Fee Rate

1. Go to **Settings** → **Fee Management**
2. Find **Overdue Fee Settings** section
3. Update **Fee Per Day** field (e.g., change to $5.00)
4. Click **Save**

✅ Changes apply immediately to new transactions

### 2. Add Grace Period

1. Go to **Settings** → **Fee Management**
2. Find **Grace Period** field under Overdue Fee Settings
3. Enter number of days (e.g., 2)
4. Click **Save**

**Example:** With 2-day grace period:
- Book due: Jan 1
- Returned: Jan 3
- Fee: $0 (within grace period)
- Returned: Jan 5
- Fee: $20 (2 days late after grace: Jan 4-5)

### 3. Set Maximum Fee Cap

Prevent excessive fines from accumulating:

1. Go to **Settings** → **Fee Management**
2. Find **Maximum Fee Cap** field
3. Enter cap amount (e.g., $100.00)
4. Click **Save**

**Example:** 
- Fee: $10/day
- Cap: $100
- 15 days late → $100 (not $150)

### 4. Configure Lost Book Fines

**Option A: Percentage Method** (Recommended)
1. Set **Fine Calculation Type** to "Percentage of Book Price"
2. Set **Fine Percentage** to 100% (or higher for handling fees)
3. Optional: Set minimum ($10) and maximum ($200) fines
4. Click **Save**

**Option B: Fixed Amount**
1. Set **Fine Calculation Type** to "Fixed Amount"
2. Enter fixed amount (e.g., $50.00)
3. Click **Save**

### 5. Enable Auto-Waive for Small Amounts

Automatically forgive tiny fees:

1. Toggle **Auto-waive Small Amounts** ON
2. Set threshold (e.g., $1.00)
3. Click **Save**

Result: Fees under $1.00 won't be charged

### 6. Change Currency

For non-USD libraries:

1. Update **Currency Symbol** (e.g., €, £, ¥)
2. Update **Currency Code** (e.g., EUR, GBP, JPY)
3. Click **Save**

## Real-World Setup Examples

### Example 1: Strict Library
```
Overdue Fee Per Day: $15.00
Grace Period: 0 days
Maximum Days: 30
Maximum Fee: $200.00
Lost Book Fine: 150% of book price
Auto-waive: Disabled
```

### Example 2: Friendly Community Library
```
Overdue Fee Per Day: $5.00
Grace Period: 3 days
Maximum Days: None
Maximum Fee: $75.00
Lost Book Fine: 100% of book price (Min: $10, Max: $100)
Auto-waive: Enabled ($1.00)
```

### Example 3: Student Library
```
Overdue Fee Per Day: $3.00
Grace Period: 2 days
Maximum Days: 20
Maximum Fee: $50.00
Lost Book Fine: $25.00 (fixed)
Auto-waive: Enabled ($2.00)
```

### Example 4: Corporate Library
```
Overdue Fee Per Day: $20.00
Grace Period: 0 days
Maximum Days: None
Maximum Fee: None
Lost Book Fine: 200% of book price
Auto-waive: Disabled
```

## How Members See Fees

### During Transaction (Return)

When staff processes a return:
1. System calculates days late
2. Applies grace period (if any)
3. Calculates fee based on current settings
4. Displays total fine
5. Fee is recorded with transaction

### Fee Display Format

**On Transaction Screen:**
```
Fine: $10.00 Per Day (After 2 Day Grace Period)
Total: $40.00
```

## Troubleshooting

### "No fine showing for overdue book"

**Check:**
- ✓ Is "Enable Overdue Fees" turned ON?
- ✓ Has the book been marked as returned?
- ✓ Is it actually late (after due date + grace period)?
- ✓ Is auto-waive threshold too high?

### "Fine seems wrong"

**Verify:**
- ✓ Current fee per day setting
- ✓ Grace period days
- ✓ Maximum day cap (if set)
- ✓ Maximum amount cap (if set)
- ✓ Calculation: (Days Late - Grace Days) × Fee Per Day

### "Can't save changes"

**Try:**
- ✓ Ensure all required fields are filled
- ✓ Check numeric fields have valid numbers
- ✓ Verify you're logged in as Admin
- ✓ Refresh page and try again

## Fee Calculation Examples

### Example 1: Basic Overdue
- Fee: $10/day
- Grace: 0 days
- Book due: Jan 1
- Returned: Jan 6
- **Calculation:** 5 days × $10 = **$50.00**

### Example 2: With Grace Period
- Fee: $10/day
- Grace: 2 days
- Book due: Jan 1
- Returned: Jan 6
- **Calculation:** (5 - 2) days × $10 = **$30.00**

### Example 3: With Maximum Days
- Fee: $10/day
- Grace: 0 days
- Max Days: 10
- Book due: Jan 1
- Returned: Jan 20 (19 days late)
- **Calculation:** 10 days × $10 = **$100.00** (capped at 10 days)

### Example 4: With Maximum Amount
- Fee: $10/day
- Grace: 0 days
- Max Amount: $75
- Book due: Jan 1
- Returned: Jan 15 (14 days late)
- **Calculation:** Min(14 × $10, $75) = **$75.00** (capped at amount)

### Example 5: Auto-Waived
- Fee: $10/day
- Grace: 0 days
- Auto-waive: $1.00
- Book due: Jan 1
- Returned: Jan 1 (2 hours late, same day)
- **Calculation:** 0 days × $10 = **$0.00** (waived)

## Best Practices

### ✅ DO:
- Set reasonable fees that encourage returns
- Use grace periods for weekends/holidays
- Review settings quarterly
- Communicate fee structure to members
- Set maximum caps to avoid shocking fines
- Enable partial payments for large fines

### ❌ DON'T:
- Set fees so high they discourage borrowing
- Change fees without notifying members
- Forget to test calculations after changes
- Disable notifications without alternative communication
- Make fees punitive rather than motivational

## Quick Tips

💡 **Start Conservative:** Begin with lower fees and grace periods, adjust if needed

💡 **Monitor Returns:** Watch return patterns after fee changes

💡 **Communicate Changes:** Email members when fee structure changes

💡 **Use the Preview:** Check "Quick Reference" section before saving

💡 **Test First:** Process a test return to verify calculations

💡 **Document Policies:** Keep written fee policy for staff reference

## Support

For additional help:
- See full documentation: `FEE_MANAGEMENT.md`
- Check transaction processing: `TRANSACTION_REFACTORING.md`
- Review membership setup: `MEMBERSHIP_CIRCULATION.md`

## Quick Reference Table

| Task | Time | Difficulty |
|------|------|------------|
| Change overdue fee rate | 30 sec | Easy |
| Add grace period | 30 sec | Easy |
| Configure lost book fines | 2 min | Easy |
| Set up auto-waive | 1 min | Easy |
| Change currency | 1 min | Easy |
| Fine-tune complex rules | 5 min | Medium |

---

**Last Updated:** December 2024  
**Version:** 1.0.0