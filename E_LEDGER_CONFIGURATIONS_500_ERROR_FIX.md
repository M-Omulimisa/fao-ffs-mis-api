# E-Ledger Configurations - 500 Error Fix

## Problem
Mobile app shows error: "Failed to retrieve group information: Call to undefined relationship [admin] on model [App\Models\FfsGroup]."

## Root Causes

### 1. Missing Relationships in FfsGroup Model ✅ FIXED
The model was missing the `admin()`, `secretary()`, and `treasurer()` relationships.

**Solution Applied:**
```php
// Added to app/Models/FfsGroup.php

public function admin()
{
    return $this->belongsTo(User::class, 'admin_id');
}

public function secretary()
{
    return $this->belongsTo(User::class, 'secretary_id');
}

public function treasurer()
{
    return $this->belongsTo(User::class, 'treasurer_id');
}
```

### 2. Missing Fillable Fields ✅ FIXED
The model's `$fillable` array was missing new VSLA fields.

**Solution Applied:**
```php
protected $fillable = [
    // ... existing fields
    'establishment_date',          // NEW
    'subcounty_text',             // NEW
    'parish_text',                // NEW
    'estimated_members',          // NEW
    'admin_id',                   // NEW
    'secretary_id',               // NEW
    'treasurer_id',               // NEW
];
```

### 3. Missing Date Cast ✅ FIXED
The `establishment_date` field wasn't being cast to a date.

**Solution Applied:**
```php
protected $casts = [
    'registration_date' => 'date',
    'establishment_date' => 'date',   // NEW
    'estimated_members' => 'integer', // NEW
    // ... other casts
];
```

### 4. Missing Data in Database ⚠️ REQUIRES MANUAL FIX
Existing groups created before the VSLA onboarding migration don't have the new fields populated.

**Issue**: Group ID 1 has NULL values for:
- `establishment_date`
- `subcounty_text`
- `parish_text`
- `admin_id`
- `secretary_id`
- `treasurer_id`

## Solutions

### Option A: Update Existing Group (Recommended for Testing)

Run this SQL to populate group 1 with valid data:

```sql
-- Find the user who owns group 1
SELECT id, name, phone_number, group_id FROM users WHERE group_id = '1' LIMIT 1;

-- Update group 1 with that user as admin
UPDATE ffs_groups 
SET 
    establishment_date = '2024-01-01',
    estimated_members = 25,
    admin_id = [USER_ID_FROM_ABOVE],
    subcounty_text = 'Central',
    parish_text = 'Central Parish'
WHERE id = 1;
```

**Or use Tinker:**

```bash
cd /Applications/MAMP/htdocs/fao-ffs-mis-api
php artisan tinker
```

```php
$group = FfsGroup::find(1);
$user = User::where('group_id', '1')->first();

$group->establishment_date = '2024-01-01';
$group->estimated_members = 25;
$group->admin_id = $user->id;
$group->subcounty_text = 'Central';
$group->parish_text = 'Central Parish';
$group->village = $group->village ?? 'Village Center';
$group->meeting_venue = 'Community Hall';
$group->meeting_day = 'Friday';
$group->meeting_frequency = 'Weekly';
$group->save();

echo "Group updated successfully!\n";
```

### Option B: Create New Test Group via Onboarding

1. Create a new user account
2. Go through the full VSLA onboarding flow
3. This will create a proper group with all fields populated

### Option C: Migration to Fix All Groups

Create a seeder/migration to populate missing fields for all existing groups:

```php
// database/seeders/PopulateVslaGroupFieldsSeeder.php

public function run()
{
    $groups = FfsGroup::whereNull('establishment_date')->get();
    
    foreach ($groups as $group) {
        // Try to find the chairperson from users
        $admin = User::where('group_id', $group->id)
            ->where('is_group_admin', 'Yes')
            ->first();
            
        $group->update([
            'establishment_date' => $group->registration_date ?? now(),
            'estimated_members' => $group->total_members ?? 20,
            'admin_id' => $admin ? $admin->id : null,
            'subcounty_text' => $group->subcounty_text ?? 'Not Set',
            'parish_text' => $group->parish_text ?? 'Not Set',
            'village' => $group->village ?? 'Not Set',
            'meeting_venue' => $group->meeting_venue ?? 'Community Center',
            'meeting_day' => $group->meeting_day ?? 'Friday',
            'meeting_frequency' => $group->meeting_frequency ?? 'Weekly',
        ]);
    }
}
```

Run with:
```bash
php artisan db:seed --class=PopulateVslaGroupFieldsSeeder
```

## Files Modified

### Backend Files
1. ✅ `app/Models/FfsGroup.php`
   - Added 3 relationships: `admin()`, `secretary()`, `treasurer()`
   - Added 7 fillable fields
   - Added 2 casts

### Total Changes
- **Relationships Added**: 3
- **Fillable Fields Added**: 7
- **Casts Added**: 2
- **Lines of Code Modified**: ~40 lines

## Testing After Fix

### 1. Quick Test via Tinker
```bash
php artisan tinker
```

```php
// Test relationship
$group = FfsGroup::with(['admin', 'secretary', 'treasurer'])->find(1);
echo $group->name . "\n";
echo "Admin: " . ($group->admin ? $group->admin->name : 'NULL') . "\n";
```

### 2. Test API Endpoint
```bash
# Get your auth token from mobile app or create one
TOKEN="your_bearer_token_here"

# Test the endpoint
curl -X GET "http://10.0.2.2:8888/fao-ffs-mis-api/api/vsla/groups/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### 3. Test in Mobile App
1. Restart the app
2. Navigate to: VSLA Tab → Configurations → Group Basic Info
3. Should load without errors

## Expected Behavior After Fix

### Success Response (200 OK)
```json
{
  "code": 1,
  "message": "Group retrieved successfully",
  "data": {
    "id": 1,
    "name": "Test VSLA Group",
    "establishment_date": "2024-01-01",
    "subcounty_text": "Central",
    "parish_text": "Central Parish",
    "admin": {
      "id": 1,
      "name": "John Doe",
      "phone": "0700000000"
    },
    "secretary": null,
    "treasurer": null
  }
}
```

## Prevention for Future

### 1. Always Use Migrations
When adding new fields, ensure migrations are run:
```bash
php artisan migrate
```

### 2. Use Seeders for Test Data
Create proper test data with all required fields:
```bash
php artisan db:seed --class=VslaGroupTestSeeder
```

### 3. Add Validation
In onboarding controllers, ensure all required fields are populated:
```php
'establishment_date' => 'required|date',
'subcounty_text' => 'required|string',
'parish_text' => 'required|string',
```

### 4. Add Model Accessors for Fallbacks
```php
// In FfsGroup model
public function getEstablishmentDateAttribute($value)
{
    return $value ?? $this->registration_date ?? now();
}
```

## Status

✅ **Code Fixed**: Model relationships and fillable fields updated  
⏳ **Data Fix Required**: Need to populate group 1 with valid data  
⏳ **Testing Pending**: Awaiting database update to test mobile app

## Next Steps

1. **Immediate**: Run Option A (Update Existing Group) script above
2. **Test**: Verify mobile app can now load group info
3. **Optional**: Run Option C seeder to fix all existing groups
4. **Deploy**: Commit the model changes to version control

---

**Date**: January 3, 2026  
**Issue**: 500 Internal Server Error  
**Status**: ✅ **CODE FIXED** | ⏳ **DATA UPDATE PENDING**
