# Login Credentials - Quick Reference

## 🔐 Access Information

### Staff Panel Login

**URL:** `http://your-domain.com/staff`

**Login Credentials:**

#### Staff Account 1
- **Email:** `catherine@gmail.com`
- **Password:** `staff001`
- **Name:** Catharine McCall
- **Role:** Staff

#### Staff Account 2
- **Email:** `lina@gmail.com`
- **Password:** `staff002`
- **Name:** Lina Carter
- **Role:** Staff

---

### Admin Panel Login

**URL:** `http://your-domain.com/admin`

**Login Credentials:**

#### Admin Account
- **Email:** `admin@gmail.com`
- **Password:** `developer`
- **Name:** Admin
- **Role:** Admin

---

## 📋 Quick Access

### Local Development

If running locally with `php artisan serve`:

**Staff Panel:**
```
http://localhost:8000/staff
```

**Admin Panel:**
```
http://localhost:8000/admin
```

---

## 🚀 Testing the Transaction Improvements

### Recommended Test Account

Use **Staff Account 1** to test the new transaction flow:

```
Email: catherine@gmail.com
Password: staff001
```

### Test Flow:
1. Log in to staff panel: `/staff`
2. Navigate to **Transactions**
3. Create a new transaction
4. Test returning books (on time and late)
5. Verify finalized transactions cannot be edited

---

## 🔄 Seeding Data

If you need to reset and reseed the database:

```bash
php artisan migrate:fresh --seed
```

This will create:
- 1 Admin user
- 2 Staff users
- 7 Borrower users (with membership types)
- 30 Books
- 10 Sample transactions

---

## 👥 Other Accounts (Borrowers)

The seeder also creates 7 borrower accounts with random names and emails.
These are used for testing transactions.

To view all users:
```bash
php artisan tinker
>>> User::with('role')->get(['name', 'email', 'role_id'])
```

---

## 🔧 Troubleshooting

### Can't Log In?

1. **Check if database is seeded:**
   ```bash
   php artisan db:seed
   ```

2. **Verify user exists:**
   ```bash
   php artisan tinker
   >>> User::where('email', 'catherine@gmail.com')->first()
   ```

3. **Reset password:**
   ```bash
   php artisan tinker
   >>> $user = User::where('email', 'catherine@gmail.com')->first();
   >>> $user->password = 'staff001';
   >>> $user->save();
   ```

### Wrong Panel?

- Staff users can only access `/staff` panel
- Admin users can only access `/admin` panel
- Borrowers cannot access either panel (they're end users)

---

## 📝 Notes

- All passwords in development are simple for testing
- **DO NOT** use these credentials in production
- Change all default passwords before deploying
- Enable proper authentication and authorization in production

---

## 🎯 Quick Test Checklist

After logging in as staff:

- [ ] Can access dashboard
- [ ] Can view transactions list
- [ ] Can create new transaction
- [ ] Can edit active (borrowed) transaction
- [ ] Can set return date and see auto-status
- [ ] Cannot edit finalized transactions
- [ ] Cannot delete finalized transactions
- [ ] Helper text shows correctly
- [ ] Fines calculate automatically

---

**Last Updated:** January 2024
**Environment:** Development/Testing