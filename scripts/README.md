# Transaction Fine Update Script

## Overview

This directory contains utility scripts for managing transaction fines.

## Scripts

### update-fines.php

Updates and recalculates fines for all returned transactions in the database.

**Purpose:**
- Recalculates fines for returned transactions
- Updates `total_fine`, `overdue_fine`, `lost_fine`, and `damage_fine` fields
- Ensures consistency between legacy `fine` field and new fee structure

**When to use:**
- After fixing fine calculation bugs
- After updating fee settings retroactively
- During data migration or maintenance

**Usage:**

```bash
# Run with DDEV
ddev exec php scripts/update-fines.php

# Or directly with PHP (if configured)
php scripts/update-fines.php
```

**Output:**
- Lists all returned transactions
- Shows which items had their fines updated
- Displays before/after values
- Provides summary statistics

**Example Output:**
```
Processing Transaction ID: 12
  Reference: TXN-20251230-0001
  Borrower: John Doe
  Returned: 2025-12-30
  Status: delayed
  ✓ Item #19 (Book Title): 13000 → 26000 cents
  Total Fine: $260.00

Summary:
  Transactions processed: 8
  Transactions updated: 1
  Items updated: 1
```

## Notes

- These scripts are safe to run multiple times
- Only updates items where necessary (detects changes)
- Uses the same calculation logic as the main application
- Does not modify non-returned transactions