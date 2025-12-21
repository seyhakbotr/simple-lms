# Membership Date Auto-Calculation

## Overview

When assigning membership to a user, the system now automatically calculates the **membership expiry date** based on the **membership type's duration** setting. This ensures consistency and eliminates manual calculation errors.

## How It Works

### 1. Membership Type Duration

Each membership type in the system has a `membership_duration_months` field that defines how long the membership lasts. For example:

- **Basic**: 12 months
- **Premium**: 24 months
- **Student**: 6 months

### 2. Automatic Calculation

When you assign or update a user's membership, the system automatically:

1. **Sets the start date** to today (if not already set)
2. **Calculates the expiry date** by adding the membership type's duration to the start date
3. **Updates both dates** whenever the membership type or start date changes

### 3. Calculation Formula

```
Expiry Date = Start Date + Membership Duration (in months)
```

**Example:**
- Start Date: January 1, 2024
- Membership Type: Premium (24 months duration)
- Calculated Expiry Date: January 1, 2026

## User Interface Behavior

### When Creating a New Borrower

1. Select the **Role** as "Borrower"
2. The membership fields appear
3. Select a **Membership Type**
   - Start date is automatically set to today
   - Expiry date is automatically calculated based on the membership duration
4. You can manually adjust the start date if needed
   - The expiry date will automatically recalculate

### When Editing an Existing Borrower

1. Change the **Membership Type**
   - The expiry date automatically recalculates based on the new type's duration
2. Change the **Start Date**
   - The expiry date automatically recalculates to maintain the correct duration
3. The expiry date field can still be manually overridden if needed

## Implementation Details

### Admin Panel (UserResource)

The membership fields are configured with reactive state management:

#### Membership Type Field
- **Live reactive**: Changes trigger immediate recalculation
- **Behavior**: When a membership type is selected:
  - Sets start date to today (if empty)
  - Calculates expiry date using the membership type's `membership_duration_months`

#### Start Date Field
- **Live reactive**: Changes trigger expiry date recalculation
- **Behavior**: When the start date changes:
  - Recalculates expiry date based on current membership type's duration

#### Expiry Date Field
- **Calculated automatically** but can be manually overridden
- **Validation**: Must be after the start date
- **Helper text**: Shows warning if membership is expired

### Model Integration

The system uses the `MembershipType` model's `membership_duration_months` field:

```php
$membershipType = MembershipType::find($membershipTypeId);
$expiryDate = Carbon::parse($startDate)
    ->addMonths($membershipType->membership_duration_months);
```

### Renewal Process

The `User` model's `renewMembership()` method also uses the same logic:

```php
public function renewMembership(): void
{
    if (!$this->membershipType) {
        return;
    }

    $startDate = $this->membershipExpired()
        ? now()
        : $this->membership_expires_at;

    $this->update([
        "membership_started_at" => $startDate,
        "membership_expires_at" => $startDate
            ->copy()
            ->addMonths($this->membershipType->membership_duration_months),
    ]);
}
```

## Benefits

✅ **Consistency**: All memberships follow the defined duration rules
✅ **Accuracy**: Eliminates manual calculation errors
✅ **Efficiency**: Staff don't need to calculate expiry dates
✅ **Flexibility**: Dates can still be manually adjusted when needed
✅ **Automatic Updates**: Changing membership type updates the expiry date accordingly

## Examples

### Example 1: New Student Member
1. Create new user, set role to "Borrower"
2. Select "Student" membership type (6 months)
3. System sets:
   - Start: December 20, 2024
   - Expiry: June 20, 2025 (automatically calculated)

### Example 2: Upgrading Membership
1. Edit existing borrower with "Basic" membership (12 months)
   - Current: Start Jan 1, 2024 → Expiry Jan 1, 2025
2. Change to "Premium" membership (24 months)
3. System recalculates:
   - Start: Jan 1, 2024 (unchanged)
   - Expiry: Jan 1, 2026 (automatically updated to 24 months from start)

### Example 3: Backdating Membership
1. Create new borrower
2. Select "Basic" membership type (12 months)
3. Change start date to October 1, 2024
4. System recalculates:
   - Start: October 1, 2024 (as entered)
   - Expiry: October 1, 2025 (automatically calculated)

## Manual Override

While the system calculates dates automatically, you can still manually adjust the expiry date if needed for special circumstances:

- Grace periods for long-time members
- Promotional extensions
- Corrections for special cases

Simply edit the "Expires" field directly after it's been auto-calculated.

## Related Documentation

- [Membership Type Integration](MEMBERSHIP_TYPE_INTEGRATION.md)
- [Membership & Circulation](MEMBERSHIP_CIRCULATION.md)
- [User Management Guide](STAFF_QUICK_GUIDE.md)

## Technical Notes

### Database Fields
- `membership_type_id` (foreign key to membership_types)
- `membership_started_at` (date)
- `membership_expires_at` (date)

### Membership Type Fields
- `membership_duration_months` (integer)
- `membership_fee` (decimal)
- Other settings (max_books_allowed, max_borrow_days, etc.)

### Validation Rules
- Expiry date must be after start date
- Membership type must be active
- Start date defaults to current date

---

**Last Updated**: December 2024
**Version**: 1.0