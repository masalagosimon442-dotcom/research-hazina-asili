# 🔧 Visitor Tracking Fix

## Problem Identified

**Issue:** Admin dashboard shows **0 visitors** despite people visiting the site.

**Root Cause:** The `site_visits` table is **missing from the database**. It was defined in migration files but NOT included in the main `postgresql_schema.sql` that was used during initial setup.

---

## What Was Fixed

### 1. **Added Missing Tables to Schema** ✅
Updated `database/postgresql_schema.sql` to include:
- `site_visits` - Tracks page visits with IP, session, user agent
- `external_searches` - Tracks PubChem/NCBI API searches
- `compound_cache` - Caches external API results

### 2. **Improved Config.php Tracking** ✅
Updated `config/config.php`:
- Added check for `Database` class before tracking
- Added debug logging in `APP_DEBUG` mode
- Prevents silent failures

### 3. **Created Diagnostic Tools** ✅
Two new files to help diagnose and fix:

**a) `check_visits.php`** - Diagnostic tool
- Shows if `site_visits` table exists
- Displays total visit counts
- Shows recent visits
- Tests recording new visits
- **Usage:** `check_visits.php?key=Admin@1234`

**b) `add_visitor_tracking.php`** - Migration script
- Automatically creates missing tables
- Verifies tables were created
- Safe to run multiple times (uses `CREATE TABLE IF NOT EXISTS`)
- **Usage:** `add_visitor_tracking.php?key=Admin@1234`

---

## How to Fix Your Live Site

### Step 1: Run Migration Script

1. Upload `add_visitor_tracking.php` to your site root
2. Open in browser: `https://hazina-asilii.onrender.com/add_visitor_tracking.php?key=Admin@1234`
3. You should see:
   - ✅ Table site_visits created/verified
   - ✅ Table external_searches created/verified
   - ✅ Table compound_cache created/verified
   - ✅ All indexes created

### Step 2: Verify Tracking Works

1. Open diagnostic tool: `https://hazina-asilii.onrender.com/check_visits.php?key=Admin@1234`
2. Check if "Total All Time Visits" shows a number > 0
3. You should see recent visits listed

### Step 3: Check Admin Dashboard

1. Go to: `https://hazina-asilii.onrender.com/views/admin/dashboard.php`
2. Look at **Visitor Statistics** section (top of page)
3. Should now show:
   - Today: X visits
   - This Week: X visits
   - This Month: X visits
   - All Time: X visits
   - Unique IPs: X visitors

### Step 4: Security Cleanup

**Delete these files after migration:**
```
✗ add_visitor_tracking.php
✗ check_visits.php
```

These are diagnostic/migration tools and should not stay on production server.

---

## Testing Locally

If you want to test on XAMPP first:

1. Start XAMPP (Apache + MySQL/PostgreSQL)
2. Open: `http://localhost/DB/project/add_visitor_tracking.php?key=Admin@1234`
3. Run migration
4. Check: `http://localhost/DB/project/check_visits.php?key=Admin@1234`
5. Visit a few pages to generate visits
6. Check admin dashboard

---

## How Visit Tracking Works

### Automatic Tracking
Every page visit is tracked in `config/config.php`:
- Only tracks GET requests (not POST/AJAX)
- Captures: IP address, page URL, user agent, session ID, user ID (if logged in)
- **Deduplication:** Same session + page within 30 minutes = counted as 1 visit

### What Gets Tracked
✅ Login page visits
✅ Dashboard views
✅ Compound search pages
✅ Profile views
✅ Any GET request page

### What Does NOT Get Tracked
❌ API calls (AJAX requests)
❌ Form submissions (POST requests)
❌ Static files (CSS, JS, images)
❌ Same page visited twice within 30 min by same session

---

## Admin Dashboard Metrics

After fix, you'll see these visitor stats:

| Metric | Description |
|--------|-------------|
| **Today** | Visits today (since midnight) |
| **This Week** | Visits in last 7 days |
| **This Month** | Visits this calendar month |
| **This Year** | Visits this calendar year |
| **All Time** | Total visits since tracking started |
| **Unique IPs** | Count of unique IP addresses |

Plus a **Daily Visitors Chart** showing last 14 days with:
- Total visits (green line)
- Unique visitors (blue dashed line)

---

## Troubleshooting

### Still showing 0 visits after migration?

**1. Check if table was created:**
```sql
SELECT COUNT(*) FROM site_visits;
```

**2. Visit a few pages to generate visits:**
- Visit login page
- Visit dashboard
- Visit search page
- Wait 1 minute, refresh dashboard

**3. Check error log:**
If `APP_DEBUG=true` in `.env`, check PHP error log for:
```
SiteVisit tracking failed: [error message]
```

**4. Verify Database class is loaded:**
The tracking code in `config.php` requires `Database` class to exist. Check:
```php
var_dump(class_exists('Database'));
```

### Visits not incrementing?

**Deduplication:** Same session visiting same page within 30 minutes = counted once.

**Solution:** Either:
- Wait 30+ minutes between visits
- Use different browser/incognito mode
- Clear cookies between tests
- Visit different pages

---

## Files Changed

### Modified Files:
1. `config/config.php` - Improved tracking with debug logging
2. `database/postgresql_schema.sql` - Added missing tables

### New Files:
1. `add_visitor_tracking.php` - Migration script (delete after use)
2. `check_visits.php` - Diagnostic tool (delete after use)
3. `VISITOR_TRACKING_FIX.md` - This documentation

---

## Summary

**Problem:** Missing `site_visits` table in database
**Solution:** Run `add_visitor_tracking.php?key=Admin@1234` to create table
**Verification:** Check `check_visits.php?key=Admin@1234` or admin dashboard
**Cleanup:** Delete migration/diagnostic files after confirming it works

After this fix, your admin dashboard will show real visitor statistics! 📊
