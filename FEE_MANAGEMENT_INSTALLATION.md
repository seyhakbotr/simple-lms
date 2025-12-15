# Fee Management Module - Installation Checklist

## Pre-Installation Requirements

✅ **System Requirements**
- [ ] Laravel 11.x installed
- [ ] Filament 3.x installed
- [ ] Spatie Laravel Settings package installed
- [ ] PHP 8.3 or higher
- [ ] Database configured and accessible

✅ **Permissions**
- [ ] Write access to database
- [ ] Admin user account available
- [ ] DDEV or local PHP environment running

---

## Installation Steps

### Step 1: Verify Existing Installation

```bash
# Check Laravel version
ddev php artisan --version

# Check if Filament is installed
ddev php artisan filament:list

# Verify Spatie Settings package
ddev composer show | grep spatie/laravel-settings
```

**Expected Output:**
- Laravel version 11.x
- Filament panels listed (admin, staff)
- Spatie settings package present

---

### Step 2: Verify Files Are Present

**Core Files:**
- [ ] `app/Settings/FeeSettings.php` - Settings class
- [ ] `app/Filament/Admin/Pages/ManageFees.php` - Admin page
- [ ] `app/Services/FeeCalculator.php` - Calculator service
- [ ] `app/Filament/Admin/Widgets/FeeStructureWidget.php` - Dashboard widget
- [ ] `resources/views/filament/admin/widgets/fee-structure-widget.blade.php` - Widget view

**Migration Files:**
- [ ] `database/settings/2025_12_15_154705_create_fee_settings.php` - Settings migration

**Modified Files:**
- [ ] `app/Models/TransactionItem.php` - Enhanced calculateFine()
- [ ] `app/Filament/Admin/Resources/TransactionResource.php` - Dynamic fee display
- [ ] `app/Filament/Staff/Resources/TransactionResource.php` - Dynamic fee display

**Documentation Files:**
- [ ] `FEE_MANAGEMENT.md` - Full documentation
- [ ] `FEE_MANAGEMENT_QUICK_START.md` - Quick start guide
- [ ] `FEE_MANAGEMENT_SUMMARY.md` - Implementation summary
- [ ] `FEE_MANAGEMENT_NAVIGATION.md` - Navigation guide
- [ ] `CHANGELOG_FEE_MANAGEMENT.md` - Changelog
- [ ] `FEE_MANAGEMENT_INSTALLATION.md` - This file

---

### Step 3: Run Database Migration

```bash
# Run the settings migration
ddev php artisan migrate
```

**Expected Output:**
```
INFO  Running migrations.

  2025_12_15_154705_create_fee_settings ....... DONE
```

**Verify Migration:**
```bash
# Check migration status
ddev php artisan migrate:status

# Check settings in database
ddev mysql -e "SELECT * FROM settings WHERE \`group\` = 'fees';" library
```

---

### Step 4: Verify Settings Initialization

```bash
# Open Tinker to verify settings
ddev php artisan tinker
```

**In Tinker:**
```php
// Get fee settings
$settings = app(\App\Settings\FeeSettings::class);

// Check overdue fee
echo $settings->overdue_fee_per_day; // Should output: 10

// Check currency
echo $settings->currency_symbol; // Should output: $

// Exit tinker
exit
```

---

### Step 5: Clear Cache

```bash
# Clear all caches
ddev php artisan cache:clear
ddev php artisan config:clear
ddev php artisan view:clear
ddev php artisan route:clear

# Rebuild caches
ddev php artisan config:cache
ddev php artisan route:cache
```

---

### Step 6: Verify Admin Access

1. **Log in to Admin Panel**
   ```
   URL: http://your-site.ddev.site/admin
   ```

2. **Navigate to Fee Management**
   - Look for "Settings" in sidebar
   - Click "Fee Management"

3. **Verify Page Loads**
   - [ ] Page loads without errors
   - [ ] All sections visible
   - [ ] Default values populated
   - [ ] Quick Reference shows summary

---

### Step 7: Test Fee Configuration

**Test 1: Change Overdue Fee**
- [ ] Go to Overdue Fee Settings
- [ ] Change "Fee Per Day" to $5.00
- [ ] Click Save
- [ ] Verify success message
- [ ] Refresh page
- [ ] Confirm value persisted

**Test 2: Add Grace Period**
- [ ] Set "Grace Period" to 2 days
- [ ] Click Save
- [ ] Verify in Quick Reference section
- [ ] Shows "After 2 Day Grace Period"

**Test 3: Configure Lost Book Fine**
- [ ] Set calculation type to "Percentage"
- [ ] Set rate to 100%
- [ ] Set minimum to $10
- [ ] Set maximum to $200
- [ ] Click Save
- [ ] Verify all values saved

---

### Step 8: Test Fee Calculation

**Create Test Transaction:**

1. **Create a past-due transaction**
   ```sql
   -- In database (adjust dates as needed)
   INSERT INTO transactions (user_id, borrowed_date, due_date, status, created_at, updated_at)
   VALUES (1, '2024-12-01', '2024-12-10', 'borrowed', NOW(), NOW());
   
   INSERT INTO transaction_items (transaction_id, book_id, borrowed_for, created_at, updated_at)
   VALUES (LAST_INSERT_ID(), 1, 14, NOW(), NOW());
   ```

2. **Return the book late**
   - Go to Transactions in Admin panel
   - Edit the test transaction
   - Set "Returned Date" to 5 days after due date
   - Set Status to "Delayed"
   - Save

3. **Verify fee calculation**
   - [ ] Fee label shows current rate
   - [ ] Total fine calculated correctly
   - [ ] Grace period applied (if set)
   - [ ] Currency symbol displayed

**Expected Calculation (with defaults):**
- Due: Dec 10
- Returned: Dec 15 (5 days late)
- Fee: $10/day
- Grace: 2 days
- **Total: $30.00** (5 - 2 = 3 days × $10)

---

### Step 9: Verify Dashboard Widget

1. **Go to Dashboard**
   ```
   URL: http://your-site.ddev.site/admin
   ```

2. **Check Fee Structure Widget**
   - [ ] Widget visible on dashboard
   - [ ] Shows current overdue fee
   - [ ] Shows grace period (if set)
   - [ ] Shows lost book settings
   - [ ] Shows payment options
   - [ ] "Configure Fees" link works

---

### Step 10: Test Staff Access

1. **Log in as Staff user**
   ```
   URL: http://your-site.ddev.site/staff
   ```

2. **Verify Transaction Display**
   - [ ] Go to Transactions
   - [ ] Edit a transaction with fine
   - [ ] Fee label shows current rate
   - [ ] Cannot access Fee Management settings

---

## Post-Installation Configuration

### Recommended Settings Review

Go to **Settings → Fee Management** and configure:

1. **Overdue Fee Settings**
   - [ ] Set appropriate fee per day for your library
   - [ ] Configure grace period (recommended: 1-3 days)
   - [ ] Consider maximum caps to prevent excessive fines
   - [ ] Enable/disable as needed

2. **Lost Book Fines**
   - [ ] Choose calculation method (percentage recommended)
   - [ ] Set min/max constraints
   - [ ] Consider your book prices when setting values

3. **Payment Settings**
   - [ ] Update currency if not USD
   - [ ] Configure partial payment option
   - [ ] Set auto-waive threshold if desired

4. **Notification Settings**
   - [ ] Enable/disable overdue notifications
   - [ ] Set notification timing

---

## Verification Checklist

### Functional Tests

✅ **Settings Management**
- [ ] Can access Fee Management page
- [ ] Can modify all settings
- [ ] Settings persist after save
- [ ] Quick Reference updates live

✅ **Fee Calculations**
- [ ] Overdue fees calculate correctly
- [ ] Grace period applied properly
- [ ] Maximum caps enforced
- [ ] Auto-waive works (if enabled)

✅ **Display**
- [ ] Dashboard widget shows correctly
- [ ] Transaction forms show dynamic labels
- [ ] Currency symbols display properly
- [ ] Amounts formatted correctly

✅ **Permissions**
- [ ] Admin can access and modify settings
- [ ] Staff can view but not modify
- [ ] Unauthorized users blocked

---

## Troubleshooting

### Issue: Migration Failed

**Solution:**
```bash
# Check migration status
ddev php artisan migrate:status

# If failed, rollback and retry
ddev php artisan migrate:rollback --step=1
ddev php artisan migrate
```

---

### Issue: Settings Not Saving

**Check:**
1. Database connection working
2. Settings table exists
3. No validation errors in browser console
4. Admin user has proper permissions

**Debug:**
```bash
# Check settings table
ddev mysql -e "DESCRIBE settings;" library

# Check for errors
ddev php artisan config:clear
ddev php artisan cache:clear
```

---

### Issue: Fee Not Calculating

**Verify:**
1. Overdue fees are enabled
2. Transaction has returned_date set
3. Grace period not hiding the fee
4. Fee not below auto-waive threshold

**Test:**
```bash
ddev php artisan tinker
```
```php
$item = \App\Models\TransactionItem::first();
$calculator = app(\App\Services\FeeCalculator::class);
$fine = $calculator->calculateOverdueFine($item);
echo $fine; // Should output amount in cents
```

---

### Issue: Widget Not Showing

**Check:**
1. Widget file exists
2. Blade view exists
3. Cache cleared
4. Dashboard configured to show widgets

**Rebuild:**
```bash
ddev php artisan view:clear
ddev php artisan filament:upgrade
```

---

## Database Backup (Recommended)

Before installation, backup your database:

```bash
# Export database
ddev export-db --file=backup_before_fees_$(date +%Y%m%d).sql.gz

# To restore if needed
ddev import-db --src=backup_before_fees_YYYYMMDD.sql.gz
```

---

## Rollback Instructions

If you need to remove the Fee Management module:

### Step 1: Rollback Migration
```bash
ddev php artisan migrate:rollback --step=1
```

### Step 2: Remove Files
```bash
rm app/Settings/FeeSettings.php
rm app/Filament/Admin/Pages/ManageFees.php
rm app/Services/FeeCalculator.php
rm app/Filament/Admin/Widgets/FeeStructureWidget.php
rm resources/views/filament/admin/widgets/fee-structure-widget.blade.php
```

### Step 3: Restore TransactionItem
Revert `app/Models/TransactionItem.php` to use hardcoded fee calculation.

### Step 4: Clear Caches
```bash
ddev php artisan cache:clear
ddev php artisan config:clear
ddev php artisan view:clear
```

---

## Support

### Getting Help

**Documentation:**
- Full Guide: `FEE_MANAGEMENT.md`
- Quick Start: `FEE_MANAGEMENT_QUICK_START.md`
- Navigation: `FEE_MANAGEMENT_NAVIGATION.md`

**Check Logs:**
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# DDEV logs
ddev logs
```

**Community:**
- GitHub Issues: [Your repo]
- Documentation: See included markdown files

---

## Success Criteria

Installation is successful when:

✅ All files are in place
✅ Migration completed successfully
✅ Settings accessible via Admin panel
✅ Default values loaded correctly
✅ Fee calculations work properly
✅ Dashboard widget displays
✅ Documentation accessible

---

## Next Steps

After successful installation:

1. **Review Documentation**
   - Read `FEE_MANAGEMENT_QUICK_START.md`
   - Familiarize yourself with features

2. **Configure for Your Library**
   - Set appropriate fee amounts
   - Configure grace periods
   - Adjust currency if needed

3. **Train Staff**
   - Show them fee display in transactions
   - Explain current fee structure
   - Document your library's policies

4. **Monitor and Adjust**
   - Watch fee calculations for accuracy
   - Gather feedback from staff
   - Adjust settings as needed

---

**Installation Guide Version:** 1.0.0  
**Last Updated:** December 15, 2024  
**Tested On:** DDEV, Laravel 11.x, Filament 3.x