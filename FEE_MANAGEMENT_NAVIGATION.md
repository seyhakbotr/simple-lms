# Fee Management - Navigation Guide

## Quick Navigation Map

```
Admin Panel
├── Dashboard
│   └── Fee Structure Widget (displays current settings)
│
└── Settings (sidebar)
    ├── General Settings
    └── Fee Management ← Configure fees here
        ├── Overdue Fee Settings
        ├── Lost Book Fine Settings
        ├── Payment Settings
        ├── Notification Settings
        └── Quick Reference (preview)
```

## How to Access Fee Management

### For Administrators

1. **Log in to Admin Panel**
   - URL: `https://your-library.com/admin`
   - Use admin credentials

2. **Navigate to Settings**
   - Look for "Settings" in the left sidebar
   - Click to expand (if collapsed)

3. **Click "Fee Management"**
   - Icon: 💵 Banknotes
   - Should be under "General Settings"
   - Opens the fee configuration page

### Visual Location

```
┌─────────────────────────────────────────┐
│ Library Management System        [User] │
├─────────────────────────────────────────┤
│                                         │
│ 🏠 Dashboard                            │
│ 📊 Reports                              │
│ 📚 Books                                │
│ 👥 Users                                │
│ 📖 Transactions                         │
│ ⚙️  Settings                    ◄━━━━━━ 1. Click here
│    ├── 🔧 General Settings              │
│    └── 💵 Fee Management      ◄━━━━━━━━ 2. Then here
│                                         │
└─────────────────────────────────────────┘
```

## Where Fees Appear

### 1. Dashboard Widget

**Location**: Admin Dashboard → Top of page
**Shows**: 
- Current overdue fee per day
- Grace period (if set)
- Lost book fine settings
- Payment options status

**Actions**:
- Click "Configure Fees" button to go to settings

---

### 2. Transaction Forms (Admin)

**Location**: Admin → Transactions → Edit Transaction

**When Book is Returned Late**:
```
┌──────────────────────────────────────┐
│ Transaction Details                  │
├──────────────────────────────────────┤
│                                      │
│ Returned Date: Jan 15, 2024         │
│ Status: Delayed                      │
│                                      │
│ Fine: $10.00 Per Day                │
│ (After 2 Day Grace Period)          │
│                                      │
│ Total: $50.00                       │
│                                      │
└──────────────────────────────────────┘
```

**Fee Label Updates**:
- Shows current per-day rate from settings
- Displays grace period if configured
- Uses currency symbol from settings

---

### 3. Transaction Forms (Staff)

**Location**: Staff → Transactions → Edit Transaction

**Same Display as Admin**:
- Staff see current fee structure
- Fee calculations are automatic
- Cannot modify fee settings (read-only)

---

### 4. Transaction List (Table View)

**Location**: Admin/Staff → Transactions → List

**Columns**:
```
| Member | Book | Due Date | Returned | Status  | Total Fine |
|--------|------|----------|----------|---------|------------|
| John   | ...  | Jan 1    | Jan 10   | Delayed | $90.00     |
```

**Total Fine Column**:
- Shows calculated fine from transaction
- Uses currency symbol from settings
- Color-coded by amount (if configured)

---

## Settings Page Layout

When you open **Settings → Fee Management**, you'll see:

```
┌─────────────────────────────────────────────────────────────┐
│ Fee Management                                     [Save]   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Left Column                  │  Right Column               │
│ ─────────────────────────   │  ─────────────────────────  │
│                              │                             │
│ ▼ Overdue Fee Settings       │  ▼ Payment Settings        │
│   • Enable Overdue Fees      │    • Currency Symbol       │
│   • Fee Per Day              │    • Currency Code         │
│   • Maximum Days to Charge   │    • Allow Partial Payment │
│   • Maximum Fee Cap          │    • Auto-waive Small      │
│   • Grace Period             │                            │
│                              │  ▼ Notification Settings   │
│ ▼ Lost Book Fine Settings    │    • Send Notifications    │
│   • Calculation Type         │    • Notification Timing   │
│   • Fine Rate/Amount         │                            │
│   • Minimum Fine             │  ▼ Quick Reference         │
│   • Maximum Fine             │    • Current Settings      │
│                              │    • Summary Preview       │
│                              │                            │
└──────────────────────────────┴─────────────────────────────┘
```

### Section Details

#### Overdue Fee Settings (Collapsible)
- Toggle to enable/disable
- Input fields for rates and caps
- Helper text for each field
- Real-time validation

#### Lost Book Fine Settings (Collapsible)
- Dropdown to select calculation type
- Dynamic fields based on selection
- Min/max inputs (for percentage method)

#### Payment Settings (Collapsible)
- Currency configuration
- Payment option toggles
- Auto-waive threshold

#### Notification Settings (Collapsible)
- Enable/disable notifications
- Timing configuration

#### Quick Reference (Always Visible)
- Live preview of settings
- Updates as you type
- Shows final fee structure

---

## Common Tasks & Where to Do Them

### Change Overdue Fee Rate
**Go to**: Settings → Fee Management → Overdue Fee Settings
**Field**: "Fee Per Day"
**Action**: Update amount, click Save

---

### Add Grace Period
**Go to**: Settings → Fee Management → Overdue Fee Settings
**Field**: "Grace Period"
**Action**: Enter number of days, click Save

---

### Set Lost Book Fine
**Go to**: Settings → Fee Management → Lost Book Fine Settings
**Field**: "Fine Calculation Type" + "Fine Rate"
**Action**: Select type, enter rate, click Save

---

### Change Currency
**Go to**: Settings → Fee Management → Payment Settings
**Fields**: "Currency Symbol" + "Currency Code"
**Action**: Update both, click Save

---

### View Current Fees
**Option 1**: Dashboard → Fee Structure Widget
**Option 2**: Settings → Fee Management → Quick Reference section
**Option 3**: Any Transaction → Fine label

---

## Mobile Navigation

On mobile devices (tablets, phones):

```
┌──────────────────────┐
│ ☰ Menu         [User]│
├──────────────────────┤
│                      │
│ Tap ☰ to open menu   │
│                      │
│ ┌────────────────┐  │
│ │ 🏠 Dashboard    │  │
│ │ 📊 Reports      │  │
│ │ 📚 Books        │  │
│ │ 👥 Users        │  │
│ │ ⚙️  Settings    │ ◄─── Tap
│ │   • General    │  │
│ │   • Fees       │ ◄─── Then tap
│ └────────────────┘  │
│                      │
└──────────────────────┘
```

---

## URL Reference

Direct links (replace `your-library.com` with your domain):

### Fee Management Page
```
https://your-library.com/admin/settings/manage-fees
```

### Dashboard (with widget)
```
https://your-library.com/admin
```

### Transaction List
```
https://your-library.com/admin/transactions
```

---

## Search & Find

### Using Browser Search (Ctrl+F / Cmd+F)

**To Find Fee Settings**:
1. Press `Ctrl+F` (Windows) or `Cmd+F` (Mac)
2. Type: "Fee Management" or "Fees"
3. Navigate through results

**To Find Specific Setting**:
1. Open Fee Management page
2. Press `Ctrl+F` / `Cmd+F`
3. Type: "Grace Period", "Lost Book", etc.

---

## Breadcrumb Navigation

When on Fee Management page, you'll see:

```
Home > Settings > Fee Management
  ↑       ↑           ↑
  │       │           └─── Current page
  │       └─────────────── Parent section
  └─────────────────────── Root
```

Click any breadcrumb to navigate back.

---

## Keyboard Shortcuts (Filament)

While on Fee Management page:

- **Tab**: Move between fields
- **Shift+Tab**: Move backwards
- **Enter**: Save (when in last field)
- **Esc**: Close dropdowns/modals
- **Ctrl+S**: Save settings (may work in some browsers)

---

## Getting Help While Navigating

### Help Text
- Hover over ℹ️ icons for tooltips
- Read gray helper text under each field
- Check Quick Reference for summary

### Documentation
From Fee Management page:
1. Note the setting name
2. Open documentation file
3. Search for that setting

**Documentation Files**:
- `FEE_MANAGEMENT_QUICK_START.md` - Quick tasks
- `FEE_MANAGEMENT.md` - Full guide
- `FEE_MANAGEMENT_SUMMARY.md` - Overview

---

## Troubleshooting Navigation

### Can't Find "Fee Management" in Settings

**Check**:
1. Are you logged in as Admin? (Staff cannot access)
2. Is Settings menu collapsed? Click to expand
3. Scroll down in Settings menu
4. Try refreshing the page (F5)

---

### Fee Management Page Won't Load

**Try**:
1. Check browser console for errors (F12)
2. Clear browser cache (Ctrl+Shift+Delete)
3. Try different browser
4. Check if migrations ran: `ddev php artisan migrate:status`

---

### Can't Save Settings

**Verify**:
1. All required fields are filled
2. Numeric fields have valid numbers
3. You're logged in as Admin
4. No browser console errors
5. Server is running

---

## Navigation Tips

💡 **Bookmark Frequently Used Pages**
- Bookmark Fee Management page for quick access
- Use browser bookmarks or favorites

💡 **Use Dashboard Widget**
- Quick view of current settings
- One-click access to configuration

💡 **Keep Documentation Handy**
- Bookmark documentation files
- Print quick reference guide

💡 **Learn Keyboard Shortcuts**
- Faster navigation
- More efficient workflow

💡 **Mobile Access**
- Fee Management works on tablets
- Best viewed in landscape mode
- All features accessible

---

## Navigation Workflow Examples

### Daily Staff Workflow
```
1. Login → Dashboard
2. Click Transactions
3. Select transaction to process
4. View auto-calculated fees
5. Complete return
```

### Admin Fee Update Workflow
```
1. Login → Dashboard
2. Settings → Fee Management
3. Update desired settings
4. Review Quick Reference
5. Click Save
6. (Optional) Test with sample transaction
```

### Monthly Review Workflow
```
1. Login → Dashboard
2. Review Fee Structure Widget
3. Check Reports (if available)
4. Settings → Fee Management
5. Adjust as needed
6. Communicate changes to staff
```

---

**Last Updated**: December 2024  
**Module Version**: 1.0.0