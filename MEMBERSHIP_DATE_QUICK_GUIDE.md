# Membership Date Auto-Calculation - Quick Guide

## 🎯 What's New?

When assigning membership to a user, the system **automatically calculates** the expiry date based on the membership type's duration. No more manual date calculations!

## ⚡ Quick Start

### Creating a New Borrower

1. **Admin Panel** → **User Management** → **Users** → **New User**
2. Fill in basic info (name, email, password)
3. Select **Role**: "Borrower"
4. In the **Membership Information** section:
   - Select a **Membership Type** (e.g., "Basic", "Premium", "Student")
   - ✨ **Start Date** is automatically set to today
   - ✨ **Expiry Date** is automatically calculated
5. Click **Create**

### Example: Creating a Student Member

```
1. Select "Student" membership type
   → System sets Start Date: Dec 20, 2024
   → System calculates Expiry Date: Dec 20, 2025 (12 months later)

2. That's it! The dates are set automatically.
```

## 🔄 How Auto-Calculation Works

### When You Select a Membership Type
```
Action: Select "Premium" membership (12 months duration)
Result:
  ✓ Start Date = Today (if empty)
  ✓ Expiry Date = Start Date + 12 months
```

### When You Change the Start Date
```
Action: Change start date to Jan 1, 2024
Result:
  ✓ Expiry Date recalculates to Jan 1, 2025
```

### When You Change the Membership Type
```
Action: Change from "Basic" to "Premium"
Result:
  ✓ Expiry Date recalculates based on new type's duration
```

## 📋 Membership Type Durations

| Membership Type | Duration | Example Start | Auto-Calculated Expiry |
|-----------------|----------|---------------|------------------------|
| Basic           | 12 months | Jan 1, 2024   | Jan 1, 2025            |
| Premium         | 12 months | Jan 1, 2024   | Jan 1, 2025            |
| Student         | 12 months | Jan 1, 2024   | Jan 1, 2025            |
| Faculty         | 12 months | Jan 1, 2024   | Jan 1, 2025            |
| Lifetime        | 1200 months (100 years) | Jan 1, 2024 | Jan 1, 2124 |

> **Note**: Durations can be customized in **Settings** → **Membership Types**

## 💡 Common Scenarios

### Scenario 1: Backdating a Membership
```
Need to register a member who joined last month?

1. Create the user and select membership type
2. Change the "Started" date to the past date
3. The expiry date automatically adjusts ✓
```

### Scenario 2: Upgrading a Member
```
Member wants to upgrade from Basic to Premium?

1. Edit the user
2. Change membership type to "Premium"
3. Expiry date recalculates from original start date ✓
```

### Scenario 3: Special Extension
```
Need to give a member extra time?

1. The expiry date is auto-calculated first
2. You can manually override it if needed
3. Just edit the "Expires" field directly ✓
```

## ✅ Benefits

✨ **No Manual Calculation** - System does the math for you  
✨ **Zero Errors** - Consistent and accurate dates  
✨ **Instant Updates** - Change type/start date = auto-recalculation  
✨ **Still Flexible** - Can manually override when needed  

## 🎓 Step-by-Step Example

### Complete Workflow: Adding a New Student Member

```
Step 1: Click "New User"
Step 2: Enter basic information
  - Name: John Doe
  - Email: john@example.com
  - Password: (create password)
  
Step 3: Select Role
  - Role: Borrower
  → Membership section appears
  
Step 4: Select Membership Type
  - Membership Type: Student
  → Start Date automatically set: Dec 20, 2024
  → Expiry Date automatically calculated: Dec 20, 2025
  
Step 5: (Optional) Adjust dates if needed
  - Change start date to Dec 1, 2024
  → Expiry Date auto-updates to: Dec 1, 2025
  
Step 6: Click "Create"
  → User created with correct membership dates! ✓
```

## 🔍 Verification

After creating/editing a user, verify the dates:

1. Go to **User Management** → **Users**
2. Find the user in the list
3. Check the "Membership Expires" column
4. ✓ Should show the correct expiry date with green/red color indicator

**Green** = Active membership  
**Red** = Expired membership

## ⚠️ Important Notes

- **Start Date** defaults to today but can be changed
- **Expiry Date** is calculated automatically but can be manually overridden
- Changing membership type **recalculates** the expiry date
- Changing start date **recalculates** the expiry date
- The calculation uses the membership type's **Duration (Months)** setting

## 🛠️ Customizing Membership Durations

To change how long memberships last:

1. Go to **Settings** → **Membership Types**
2. Edit the membership type
3. Change the **Membership Duration (Months)** field
4. Save
5. New assignments will use the updated duration

## 📚 Related Features

- **Membership Renewal**: See `User::renewMembership()` method
- **Membership Validation**: Checked during book borrowing
- **Expiry Warnings**: Shown in red when expired

## 🆘 Troubleshooting

**Q: Expiry date not calculating?**  
A: Make sure the membership type has a duration set (check Membership Types settings)

**Q: Can I manually set the expiry date?**  
A: Yes! It auto-calculates first, but you can override it manually

**Q: What happens when I change membership type?**  
A: The expiry date recalculates from the start date using the new type's duration

**Q: Can I backdate memberships?**  
A: Yes! Just change the start date and the expiry will auto-adjust

---

**Quick Reference**: Start Date + Membership Duration = Expiry Date  
**Last Updated**: December 2024