# Membership Date Auto-Calculation - Test Cases

## Test Case 1: New User with Membership Type Selection

### Setup
- Navigate to Admin Panel → Users → New User
- Fill in name, email, password
- Select role: "Borrower"

### Test Steps
1. Select Membership Type: "Basic"
2. Observe Start Date field
3. Observe Expiry Date field

### Expected Results
- ✓ Start Date = Current date (auto-set)
- ✓ Expiry Date = Current date + 12 months
- ✓ Both dates are automatically populated

### Example
```
Today: December 20, 2024
Selected: Basic (12 months)

Expected:
- Start Date: 2024-12-20
- Expiry Date: 2025-12-20
```

---

## Test Case 2: Changing Membership Type

### Setup
- Create a user with "Basic" membership
- Start Date: January 1, 2024
- Expiry Date: January 1, 2025 (auto-calculated)

### Test Steps
1. Edit the user
2. Change Membership Type to "Lifetime" (1200 months)
3. Observe Expiry Date field

### Expected Results
- ✓ Start Date remains: January 1, 2024
- ✓ Expiry Date recalculates to: January 1, 2124 (100 years later)

---

## Test Case 3: Changing Start Date

### Setup
- Create a user with "Student" membership
- Start Date: December 20, 2024
- Expiry Date: December 20, 2025 (auto-calculated)

### Test Steps
1. Edit the user
2. Change Start Date to: October 1, 2024
3. Observe Expiry Date field

### Expected Results
- ✓ Start Date updated: October 1, 2024
- ✓ Expiry Date recalculates to: October 1, 2025

---

## Test Case 4: Sequential Type Changes

### Setup
- Create a new user, select role "Borrower"

### Test Steps
1. Select "Basic" → Observe dates
2. Change to "Premium" → Observe dates
3. Change to "Faculty" → Observe dates
4. Change to "Student" → Observe dates

### Expected Results
Each change should recalculate the expiry date:
```
Basic (12 months):
  Start: 2024-12-20 → Expiry: 2025-12-20

Premium (12 months):
  Start: 2024-12-20 → Expiry: 2025-12-20

Faculty (12 months):
  Start: 2024-12-20 → Expiry: 2025-12-20

Student (12 months):
  Start: 2024-12-20 → Expiry: 2025-12-20
```

---

## Test Case 5: Backdating Membership

### Setup
- Navigate to create new user
- Select role: "Borrower"

### Test Steps
1. Select Membership Type: "Premium"
2. Observe auto-calculated dates
3. Change Start Date to: June 1, 2024
4. Observe Expiry Date

### Expected Results
- ✓ Initial: Start = 2024-12-20, Expiry = 2025-12-20
- ✓ After change: Start = 2024-06-01, Expiry = 2025-06-01

---

## Test Case 6: Manual Override of Expiry Date

### Setup
- Create user with "Basic" membership
- Start: Jan 1, 2024
- Expiry: Jan 1, 2025 (auto-calculated)

### Test Steps
1. Edit the user
2. Manually change Expiry Date to: March 1, 2025
3. Save the user
4. Re-open the user

### Expected Results
- ✓ Expiry Date remains: March 1, 2025 (manual override preserved)
- ✓ No automatic recalculation on re-open

---

## Test Case 7: Validation - Expiry Before Start

### Setup
- Create user with "Basic" membership
- Start: Jan 1, 2024
- Expiry: Jan 1, 2025 (auto-calculated)

### Test Steps
1. Edit the user
2. Try to manually set Expiry Date to: December 1, 2023 (before start date)
3. Attempt to save

### Expected Results
- ✓ Validation error shown
- ✓ Error message: "Expiry date must be after start date" (or similar)
- ✓ Form does not save

---

## Test Case 8: Different Membership Types - Same Start Date

### Setup
- Current date: January 1, 2024

### Test Steps
Create 5 users with the same start date but different membership types:

| User | Type | Duration | Expected Expiry |
|------|------|----------|-----------------|
| User A | Basic | 12 months | Jan 1, 2025 |
| User B | Premium | 12 months | Jan 1, 2025 |
| User C | Student | 12 months | Jan 1, 2025 |
| User D | Faculty | 12 months | Jan 1, 2025 |
| User E | Lifetime | 1200 months | Jan 1, 2124 |

### Expected Results
- ✓ All users have correct calculated expiry dates
- ✓ Lifetime membership shows 100 years duration

---

## Test Case 9: Editing Existing User Without Membership

### Setup
- Edit an existing user who has no membership assigned
- Role is already "Borrower"

### Test Steps
1. Select Membership Type: "Premium"
2. Observe dates

### Expected Results
- ✓ Start Date = Current date (auto-set)
- ✓ Expiry Date = Current date + 12 months
- ✓ Fields populate even for existing users

---

## Test Case 10: Creating User with Future Start Date

### Setup
- Current date: December 20, 2024
- Create new user, role: "Borrower"

### Test Steps
1. Select Membership Type: "Basic"
2. Change Start Date to: January 15, 2025 (future date)
3. Observe Expiry Date

### Expected Results
- ✓ Start Date: January 15, 2025
- ✓ Expiry Date: January 15, 2026 (12 months from start)
- ✓ System allows future start dates

---

## Test Case 11: Renewal Simulation

### Setup
- User with expired membership:
  - Start: Jan 1, 2023
  - Expiry: Jan 1, 2024 (expired)
  - Type: Basic (12 months)

### Test Steps
1. Edit the user
2. Change Start Date to: Current date
3. Observe Expiry Date

### Expected Results
- ✓ Start Date: 2024-12-20 (current)
- ✓ Expiry Date: 2025-12-20 (12 months from new start)
- ✓ Membership renewed correctly

---

## Test Case 12: Rapid Type Switching

### Setup
- Edit a user with "Basic" membership
- Start: Jan 1, 2024

### Test Steps
1. Change to "Premium" → Note expiry
2. Immediately change to "Student" → Note expiry
3. Immediately change to "Lifetime" → Note expiry
4. Change back to "Basic" → Note expiry

### Expected Results
- ✓ Each change recalculates expiry correctly
- ✓ No lag or incorrect calculations
- ✓ Final expiry matches "Basic" duration

---

## Edge Cases

### Edge Case 1: Membership Type with No Duration
**Scenario**: If a membership type has no duration set (null or 0)
**Expected**: Expiry date should not auto-calculate or show error

### Edge Case 2: Leap Year Calculation
**Scenario**: Start Date: Feb 29, 2024 (leap year), Type: Basic (12 months)
**Expected**: Expiry Date: Feb 28, 2025 (or Mar 1, 2025 depending on Carbon behavior)

### Edge Case 3: End of Month Dates
**Scenario**: Start Date: Jan 31, 2024, Type: Basic (12 months)
**Expected**: Expiry Date: Jan 31, 2025

---

## Regression Tests

### Regression 1: Editing Without Changing Membership
**Test**: Edit user, change only name/email, save
**Expected**: Membership dates remain unchanged

### Regression 2: Creating Non-Borrower User
**Test**: Create user with role "Admin" or "Staff"
**Expected**: Membership fields not visible, no auto-calculation

### Regression 3: Bulk Import
**Test**: If users are imported via seeder/factory
**Expected**: Manual date setting still works as before

---

## Performance Tests

### Performance 1: Multiple Users Creation
**Test**: Create 50 users with different membership types
**Expected**: Each user has correct auto-calculated dates, no timeouts

### Performance 2: Quick Edit/Save Cycles
**Test**: Edit → Change type → Save → Edit → Change type → Save (repeat 10 times)
**Expected**: No degradation, all calculations accurate

---

## Integration Tests

### Integration 1: With Transaction Creation
**Test**: Create user with membership, then create transaction
**Expected**: Transaction validates against correct expiry date

### Integration 2: With Membership Expiry Warnings
**Test**: Create user with past expiry date
**Expected**: Expiry warning shows correctly in user list and transaction creation

---

## Verification Checklist

After implementing, verify:

- [ ] Auto-calculation works on create
- [ ] Auto-calculation works on edit
- [ ] Changing membership type recalculates
- [ ] Changing start date recalculates
- [ ] Manual override is possible
- [ ] Validation prevents expiry before start
- [ ] No errors in browser console
- [ ] No PHP errors in logs
- [ ] All membership types work correctly
- [ ] Dates persist correctly in database
- [ ] UI is responsive and smooth
- [ ] Helper text displays correctly

---

**Test Environment**: Admin Panel → User Management → Users
**Required Access**: Admin role
**Last Updated**: December 2024