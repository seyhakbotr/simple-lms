# Transaction Flow - Quick Start Guide

## 🚀 Quick Actions

### Borrow Books (Create Transaction)
1. Go to **Transactions** → **Create**
2. Select borrower
3. Set borrow duration (defaults to membership max)
4. Select books
5. Click **Create**

### Return Books
1. Find transaction in **Transactions** list
2. Click **Actions** → **Return Books**
3. Set return date (defaults to today)
4. Mark any lost/damaged books
5. Review fee preview
6. Click **Process Return**

### Renew Transaction
1. Open transaction details
2. Click **Renew Transaction**
3. Confirm renewal

### View Transaction
1. Find transaction in list
2. Click **Actions** → **View Details**

---

## 📊 Transaction Statuses

| Status | Meaning |
|--------|---------|
| **Borrowed** | Currently out, not yet due |
| **Returned** | Returned on time, no issues |
| **Delayed** | Returned late |
| **Lost** | One or more books lost |
| **Damaged** | One or more books damaged |

---

## 💰 Fee Types

### Overdue Fees
- Calculated automatically when returned late
- Based on: Days late × Fee per day
- Respects grace period
- Capped by maximum amount

### Lost Book Fees
- Based on book's replacement cost
- Applied when book marked as lost
- Book NOT returned to stock

### Damage Fees
- Manually entered by staff
- Add notes describing damage
- Book returned to stock

---

## ✅ Validation Checks

### When Borrowing:
✓ User has active membership  
✓ Not exceeded borrowing limit  
✓ Books are available  
✓ Borrow duration within limits  

### When Returning:
✓ Transaction not already returned  
✓ Return date is valid  
✓ Damage fees are non-negative  

### When Renewing:
✓ Not already returned  
✓ Not currently overdue  
✓ Membership is active  
✓ Renewal limit not reached  

---

## 🔑 Key Features

### Smart Defaults
- Borrow duration = membership type's max days
- Return date = today
- Reference number = auto-generated

### Real-time Validation
- Shows borrowing capacity when selecting user
- Live fee preview when returning
- Instant validation feedback

### Membership Integration
- Enforces max books allowed
- Respects max borrow days
- Checks renewal limits
- Validates membership expiry

---

## 📝 Common Workflows

### Standard Borrow & Return
```
1. Create Transaction
   - Select borrower
   - Select books (1-3 books)
   - Use default 14 days
   
2. Return On Time
   - Set return date
   - No fees
   - Status: Returned
```

### Late Return with Fee
```
1. Return Late
   - Set return date (after due date)
   - System calculates overdue fee
   - Review fee preview
   - Status: Delayed
```

### Lost Book
```
1. Return with Lost Book
   - Set return date
   - Mark book as lost
   - System calculates replacement fee
   - Status: Lost
```

### Damaged Book
```
1. Return with Damage
   - Set return date
   - Mark book as damaged
   - Enter damage fine
   - Add damage notes
   - Status: Damaged
```

---

## 🎯 Tips & Best Practices

### Creating Transactions
- Check user's membership status first
- Don't exceed borrowing capacity
- Use appropriate duration for book type
- Verify book availability

### Processing Returns
- Double-check return date
- Review fee preview carefully
- Add detailed damage notes
- Confirm fee amounts before processing

### Managing Renewals
- Renew before due date
- Check renewal count
- Verify membership is active
- Communicate new due date to user

---

## ⚠️ Common Issues

**"User has no membership type"**
→ Assign membership type to user first

**"Borrowing limit reached"**
→ User has too many active transactions

**"Membership expired"**
→ Renew user's membership first

**"Cannot renew: overdue"**
→ Process return first, then create new transaction

**"Book not available"**
→ Check stock, may be already borrowed

---

## 📱 Quick Reference

### Transaction Data Flow
```
Create → Borrowed → [Renew] → Return → Finalized
                      ↓            ↓
                  Borrowed    (Returned/Delayed/Lost/Damaged)
```

### Fee Calculation
```
Total = Overdue Fee + Lost Fee + Damage Fee

Overdue = Days Late × Fee/Day (after grace period)
Lost = Book Price × % OR Fixed Amount
Damage = Manual Entry
```

### Membership Limits
```
Max Books: Controls how many books can be borrowed
Max Days: Default and maximum borrow duration
Renewals: How many times transaction can be renewed
```

---

## 🔍 Where to Find...

**Active Transactions:** Filter by status "Borrowed"  
**Overdue Transactions:** Use "Overdue Only" filter  
**User's History:** Click user → View transactions  
**Fee Settings:** Settings → Fee Management  
**Membership Types:** Settings → Membership Types  

---

## 🛠️ Admin vs Staff

Both panels have same functionality:
- `/admin` - Admin panel
- `/staff` - Staff panel

Features available to both:
- Create/Return/View/Renew transactions
- Process fees
- Manage all operations

---

## 📞 Need Help?

1. **Read full documentation:** TRANSACTION_FLOW_V2.md
2. **Service layer details:** TRANSACTION_SERVICE_GUIDE.md
3. **Fee configuration:** FEE_MANAGEMENT.md
4. **Membership setup:** MEMBERSHIP_TYPE_INTEGRATION.md

---

## 🎓 Training Checklist

For new staff:
- [ ] Create a test transaction
- [ ] Return transaction on time
- [ ] Return transaction late (see fees)
- [ ] Mark book as lost
- [ ] Mark book as damaged
- [ ] Renew a transaction
- [ ] View transaction history
- [ ] Use filters to find transactions

---

**Quick Tip:** Most operations show helpful hints and validation messages. Read them carefully!

**Remember:** You cannot edit returned transactions. Process returns carefully!

**Best Practice:** Always review the fee preview before confirming a return.