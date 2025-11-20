# Quick Testing Guide - Role-Based Access Control

## Setup Test Users

### 1. Create Manager Role (if not exists)
```sql
-- Check if manager role exists
SELECT * FROM admin_roles WHERE slug = 'manager';

-- If not exists, create it
INSERT INTO admin_roles (name, slug, created_at, updated_at) 
VALUES ('Manager', 'manager', NOW(), NOW());
```

### 2. Create Test Users

**Admin User:**
- Username: admin_test
- Role: admin

**Manager User:**
- Username: manager_test  
- Role: manager

```sql
-- Assign admin role (role_id = 1)
INSERT INTO admin_role_users (role_id, user_id) 
VALUES (1, [admin_user_id]);

-- Assign manager role (role_id = 2)
INSERT INTO admin_role_users (role_id, user_id) 
VALUES (2, [manager_user_id]);
```

---

## Test Scenarios

### Test 1: Dashboard Access
**Login as Admin:**
- ✅ Should see full dashboard
- ✅ Revenue charts visible
- ✅ Financial overview visible
- ✅ Payment gateway stats visible
- ✅ Recent activities visible

**Login as Manager:**
- ✅ Should see basic dashboard
- ❌ No revenue charts
- ❌ No financial details
- ❌ No payment gateway stats
- ❌ No recent activities

### Test 2: Project Management
**URLs to test:**
- `/admin/projects` - List
- `/admin/projects/create` - Create
- `/admin/projects/1/edit` - Edit

**Admin:**
- ✅ Can view list
- ✅ Can create new
- ✅ Can edit existing
- ✅ Can delete

**Manager:**
- ❌ Redirected or blocked from all project routes

### Test 3: Insurance Programs
**URLs to test:**
- `/admin/insurance-programs` - List
- `/admin/insurance-programs/create` - Create

**Admin:**
- ✅ Can view list
- ✅ Create button visible
- ✅ Edit/Delete actions visible

**Manager:**
- ✅ Can view list
- ❌ Create button hidden
- ❌ Edit/Delete actions hidden

### Test 4: User Management
**URLs to test:**
- `/admin/users` - List
- `/admin/users/create` - Create
- `/admin/users/1/edit` - Edit

**Admin:**
- ✅ Can view users
- ✅ Can create users
- ✅ Can edit users
- ✅ Password fields visible

**Manager:**
- ✅ Can view users
- ❌ Cannot create/edit/delete

### Test 5: Financial Routes (Direct URL Access)
**Try accessing these URLs as Manager:**
- `/admin/project-transactions`
- `/admin/withdraw-requests`
- `/admin/disbursements`
- `/admin/insurance-subscription-payments`

**Expected Result:**
- ❌ Should get "Access Denied" message
- ❌ Redirected back

---

## Quick Commands

### Check User Roles
```bash
cd /Applications/MAMP/htdocs/dtehm-insurance-api
php artisan tinker
```

```php
// In tinker
$user = \Encore\Admin\Auth\Database\Administrator::find([USER_ID]);
echo $user->isRole('admin') ? 'Is Admin' : 'Not Admin';
echo $user->isRole('manager') ? 'Is Manager' : 'Not Manager';

// List all roles for user
$user->roles->pluck('name');
```

### Clear Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## Expected Behavior Summary

| Feature | Admin | Manager |
|---------|-------|---------|
| Full Dashboard | ✅ Yes | ❌ No |
| Financial Charts | ✅ Yes | ❌ No |
| Payment Stats | ✅ Yes | ❌ No |
| View Projects | ✅ Yes | ❌ No |
| Create/Edit Projects | ✅ Yes | ❌ No |
| View Insurance | ✅ Yes | ✅ Yes |
| Edit Insurance | ✅ Yes | ❌ No |
| View Users | ✅ Yes | ✅ Yes |
| Manage Users | ✅ Yes | ❌ No |
| System Settings | ✅ Yes | ❌ No |
| Withdraw Requests | ✅ Yes | ❌ No |

---

## If Tests Fail

1. **Clear all caches:**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```

2. **Verify middleware is registered:**
   - Check `app/Http/Kernel.php`
   - Look for `'admin.only' => \App\Http\Middleware\AdminOnly::class`

3. **Verify roles exist:**
   ```sql
   SELECT * FROM admin_roles;
   SELECT * FROM admin_role_users WHERE user_id = [USER_ID];
   ```

4. **Check trait is included:**
   - Controllers should have `use RoleBasedDashboard;`

5. **Logout and login again**
   - Sometimes session needs refresh

---

## Success Criteria

✅ All tests pass for admin user  
✅ All restrictions work for manager user  
✅ No errors in browser console  
✅ Proper error messages shown  
✅ Dashboard renders correctly for both roles  

---

**Ready for Production:** After all tests pass ✅
