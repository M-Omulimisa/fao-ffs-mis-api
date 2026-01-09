# 🚀 Shareout Module - Quick Reference Guide

## 📍 Quick Navigation

### Documentation Files
- **Complete Spec:** `SHAREOUT_MODULE_DOCUMENTATION.md`
- **Security Details:** `SHAREOUT_VALIDATION_AND_SECURITY_ENHANCEMENTS.md`
- **Production Summary:** `SHAREOUT_MODULE_FINAL_PRODUCTION_SUMMARY.md`
- **Testing Guide:** `SHAREOUT_MODULE_TESTING_GUIDE.md`
- **Deployment Checklist:** `SHAREOUT_DEPLOYMENT_CHECKLIST.md`
- **Completion Report:** `SHAREOUT_COMPLETION_REPORT.md`

---

## 🔗 API Endpoints Cheat Sheet

Base URL: `https://api.example.com/api/vsla/shareouts`

| Method | Endpoint | Purpose | Auth | Body |
|--------|----------|---------|------|------|
| GET | `/available-cycles` | Get cycles for shareout | ✅ | - |
| POST | `/initiate` | Create new shareout | ✅ | `{cycle_id}` |
| POST | `/{id}/calculate` | Calculate distributions | ✅ | `{}` |
| GET | `/{id}/distributions` | Get member breakdown | ✅ | - |
| GET | `/{id}/summary` | Get financial summary | ✅ | - |
| POST | `/{id}/approve` | Mark as approved | ✅ | `{notes?}` |
| POST | `/{id}/complete` | Finalize & close cycle | ✅ | `{}` |
| POST | `/{id}/cancel` | Cancel shareout | ✅ | `{}` |
| GET | `/{id}` | Get details | ✅ | - |
| GET | `/history` | List all shareouts | ✅ | - |

**Headers Required:**
```
Authorization: Bearer {token}
User-Id: {user_id}
Content-Type: application/json
Accept: application/json
```

---

## 🔄 State Machine Quick Reference

```
draft → calculated → approved → completed
  ↓         ↓          ↓
        cancelled
```

**Valid Transitions:**
- `draft` → `calculated`, `cancelled`
- `calculated` → `approved`, `cancelled`
- `approved` → `completed`, `cancelled`
- `completed` → (terminal state)
- `cancelled` → (terminal state)

---

## 🎯 Common Use Cases

### 1. Create Complete Shareout
```bash
# Step 1: Get available cycles
GET /api/vsla/shareouts/available-cycles

# Step 2: Initiate
POST /api/vsla/shareouts/initiate
Body: {"cycle_id": 7}

# Step 3: Calculate
POST /api/vsla/shareouts/{id}/calculate

# Step 4: Approve
POST /api/vsla/shareouts/{id}/approve

# Step 5: Complete
POST /api/vsla/shareouts/{id}/complete
```

### 2. View Shareout Details
```bash
GET /api/vsla/shareouts/{id}/summary
GET /api/vsla/shareouts/{id}/distributions
```

### 3. Cancel Shareout
```bash
POST /api/vsla/shareouts/{id}/cancel
```

---

## 🐛 Common Issues & Quick Fixes

### Issue: 401 Unauthorized
**Fix:** Check token validity, re-login if expired

### Issue: 403 Forbidden
**Fix:** Verify user belongs to same group as shareout

### Issue: 400 Bad Request "Cannot approve"
**Fix:** Must calculate first, status should be 'calculated'

### Issue: Distributions not showing
**Fix:** Verify calculateDistributions() was called successfully

### Issue: Cycle not in available list
**Fix:** Check cycle is active and has no existing shareout

---

## 📁 Key Files Reference

### Backend
```php
// Controller
app/Http/Controllers/Api/VslaShareoutController.php

// Service
app/Services/ShareoutCalculationService.php

// Models
app/Models/VslaShareout.php
app/Models/VslaShareoutDistribution.php

// Request
app/Http/Requests/InitiateShareoutRequest.php

// Routes
routes/api.php (line ~850)
```

### Frontend
```dart
// Service
lib/services/vsla_shareout_service.dart

// Screens
lib/screens/vsla/configurations/ShareoutWizardScreen.dart
lib/screens/vsla/configurations/ShareoutHistoryScreen.dart
lib/screens/vsla/configurations/ShareoutDetailsScreen.dart

// Models
lib/models/vsla_shareout_models.dart
```

---

## 💾 Database Quick Reference

### Tables
- `vsla_shareouts` - Main shareout records
- `vsla_shareout_distributions` - Member distributions

### Important Columns
```sql
-- vsla_shareouts
id, cycle_id, group_id, status, 
total_savings, total_shares, share_value,
approved_by, approved_at

-- vsla_shareout_distributions
id, shareout_id, member_id,
shares_count, savings_total, final_payout
```

### Indexes
```sql
-- vsla_shareouts
INDEX (cycle_id)
INDEX (group_id)
INDEX (status)
UNIQUE (cycle_id, group_id, status) WHERE status NOT IN ('cancelled', 'completed')

-- vsla_shareout_distributions
INDEX (shareout_id)
INDEX (member_id)
UNIQUE (shareout_id, member_id)
```

---

## 🧪 Testing Quick Commands

### Test Backend
```bash
# Make script executable
chmod +x test_shareout_module.sh

# Run tests
./test_shareout_module.sh
```

### Test Frontend
```bash
# Run on device
flutter run --release

# Build APK
flutter build apk --release --split-per-abi
```

---

## 🚀 Deployment Quick Steps

### Backend
```bash
# Pull code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Optimize
php artisan config:cache
php artisan route:cache
php artisan optimize
```

### Frontend
```bash
# Clean
flutter clean
flutter pub get

# Build release
flutter build apk --release --split-per-abi

# Install on device
flutter install --release
```

---

## 📊 Monitoring Commands

### Check Logs
```bash
# Watch logs
tail -f storage/logs/laravel.log

# Search for errors
grep "ERROR" storage/logs/laravel-$(date +%Y-%m-%d).log

# Search shareout logs
grep "shareout" storage/logs/laravel.log
```

### Database Queries
```sql
-- Count shareouts by status
SELECT status, COUNT(*) FROM vsla_shareouts GROUP BY status;

-- Recent shareouts
SELECT * FROM vsla_shareouts ORDER BY created_at DESC LIMIT 10;

-- Distributions for shareout
SELECT * FROM vsla_shareout_distributions WHERE shareout_id = 3;
```

---

## 🔧 Emergency Procedures

### Rollback Backend
```bash
# Revert last commit
git revert HEAD
git push origin main

# Rollback database
php artisan migrate:rollback --step=1

# Restore from backup
mysql -u root -p fao_ffs_mis_db < backup.sql
```

### Fix Stuck Shareout
```sql
-- Reset status to draft
UPDATE vsla_shareouts SET status = 'draft' WHERE id = X;

-- Delete distributions
DELETE FROM vsla_shareout_distributions WHERE shareout_id = X;
```

---

## 📞 Contact Information

### Development Team
- **Backend:** [Name] - [Email] - [Phone]
- **Frontend:** [Name] - [Email] - [Phone]
- **DevOps:** [Name] - [Email] - [Phone]

### On-Call Support
- **Week 1:** [Name] - [Phone]
- **Week 2:** [Name] - [Phone]

---

## 📝 Useful Links

- **API Documentation:** `/api/documentation`
- **Telescope:** `/telescope` (dev only)
- **Error Tracking:** Sentry Dashboard
- **Analytics:** Google Analytics / Firebase

---

## ✅ Pre-Deploy Checklist (Quick)

Backend:
- [ ] Code pulled
- [ ] Dependencies installed
- [ ] Migrations run
- [ ] Caches cleared
- [ ] Tests passed

Frontend:
- [ ] Code updated
- [ ] Dependencies installed
- [ ] Build successful
- [ ] Tested on device
- [ ] APK generated

Database:
- [ ] Backup created
- [ ] Migrations tested
- [ ] Indexes verified

Monitoring:
- [ ] Logs accessible
- [ ] Errors tracked
- [ ] Team notified

---

## 🎯 Quick Test Scenario

**5-Minute Smoke Test:**
1. Login to app ✅
2. Navigate to Shareout History ✅
3. Tap "+" to create new ✅
4. Select active cycle ✅
5. Initiate shareout ✅
6. Calculate distributions ✅
7. View member breakdown ✅
8. View summary ✅
9. Approve shareout ✅
10. Complete shareout ✅

**Expected:** All steps complete without errors in < 5 minutes

---

## 🔐 Security Checklist (Quick)

- [x] Authentication on all endpoints
- [x] Authorization group-level
- [x] Input validation
- [x] SQL injection prevention
- [x] XSS protection
- [x] State machine enforcement
- [x] Error messages safe
- [x] Logs no sensitive data

---

## 📈 Performance Targets

- API Response: < 500ms (95th percentile)
- UI Frame Rate: 60 FPS
- Database Queries: < 100ms
- App Launch: < 3 seconds
- Memory Usage: < 200MB

---

## 🎉 Success Metrics

**Week 1 Targets:**
- Shareouts created: 50+
- Completion rate: 80%+
- Error rate: < 1%
- User satisfaction: > 4/5

**Monitor:**
- Error logs daily
- Performance metrics hourly
- User feedback continuously

---

## 📚 Further Reading

For detailed information, see:
- Architecture: `SHAREOUT_MODULE_DOCUMENTATION.md`
- Security: `SHAREOUT_VALIDATION_AND_SECURITY_ENHANCEMENTS.md`
- Testing: `SHAREOUT_MODULE_TESTING_GUIDE.md`
- Deployment: `SHAREOUT_DEPLOYMENT_CHECKLIST.md`
- Complete Report: `SHAREOUT_COMPLETION_REPORT.md`

---

**Last Updated:** 2025-08-30  
**Version:** 1.0.0  
**Status:** ✅ Production Ready

**🚀 Quick Reference Guide Complete - Happy Coding! 🚀**
