# VPS Database Sync Setup (Local Testing Only)

## Overview
This setup syncs `punch_logs` data from VPS database to your local database. This allows you to test with live data locally without affecting production. **This is for local testing only** and sync code will **never go to production server**.

## Setup Instructions

### Step 1: Add VPS Connection to Local `.env`

Add these lines to your **local** `.env` file (DO NOT commit this file):

```env
# VPS Database Connection (for sync only - local testing)
VPS_DB_HOST=72.60.201.175
VPS_DB_PORT=3306
VPS_DB_DATABASE=logintask
VPS_DB_USERNAME=task5login
VPS_DB_PASSWORD=nehapalagra
```

### Step 2: Run Initial Sync

Run the sync command to copy VPS data to local:

```bash
php artisan sync:vps-punch-logs --full
```

The `--full` flag performs a complete sync the first time. After that, it will only sync new records.

### Step 3: Verify Sync

Check if data was synced:

```bash
php artisan tinker
```

Then in tinker:
```php
DB::table('punch_logs')->count();
```

This should show the number of records synced from VPS.

## How It Works

1. **Sync Command**: `php artisan sync:vps-punch-logs` connects to VPS database (read-only)
2. **Incremental Sync**: Only fetches new records since last sync (tracked by ID)
3. **Local Storage**: Inserts records into your local `punch_logs` table
4. **All Queries**: Use local database (no direct VPS queries in application code)
5. **Sync Tracker**: Tracks last synced ID to avoid duplicates

## Safety Features

✅ **Read-only access**: The VPS user should have SELECT only permissions  
✅ **Local only**: Sync code will NOT go to production (keep in local branch)  
✅ **No production impact**: Production server never runs sync command  
✅ **Incremental**: Only syncs new records, avoids duplicates  
✅ **Isolated**: Only affects local `punch_logs` table  

## Running Sync

**Manual Sync:**
```bash
php artisan sync:vps-punch-logs
```

**Full Sync (re-sync all records):**
```bash
php artisan sync:vps-punch-logs --full
```

**Automatic Sync (every 15 minutes):**
Add to `app/Console/Kernel.php` schedule (local only):
```php
$schedule->command('sync:vps-punch-logs')->everyFifteenMinutes();
```

Then run scheduler:
```bash
php artisan schedule:work
```

## Important Notes

⚠️ **Never commit `.env` file** - Keep VPS credentials local only  
⚠️ **Never enable in production** - This is for local testing only  
⚠️ **Requires internet** - You need to be connected to access VPS  
⚠️ **Performance** - Queries will be slower due to network latency  

## Troubleshooting

**Connection refused:**
- Check if VPS MySQL allows remote connections
- Verify firewall allows port 3306
- Confirm IP address is correct

**Access denied:**
- Verify username/password
- Check if user has SELECT permissions
- Ensure user can connect from your IP

**Table not found:**
- Verify database name is correct
- Check if `punch_logs` table exists on VPS
