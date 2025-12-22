# VPS Database Sync Guide

## Overview

This guide explains how to sync all related tables from the VPS server to your local database for testing.

## Available Commands

### 1. Sync All Tables (Recommended)
```bash
php artisan sync:vps-all-tables --full
```

This command syncs **all** related tables:
- `punch_logs` - Machine punch records
- `manual_attendances` - Manual login/logout actions
- `whatsapp_logs` - WhatsApp message sent status
- `notification_queue` - Notification queue entries

### 2. Sync Specific Table
```bash
# Sync only punch_logs
php artisan sync:vps-all-tables --full --table=punch_logs

# Sync only manual_attendances
php artisan sync:vps-all-tables --full --table=manual_attendances

# Sync only whatsapp_logs
php artisan sync:vps-all-tables --full --table=whatsapp_logs

# Sync only notification_queue
php artisan sync:vps-all-tables --full --table=notification_queue
```

### 3. Incremental Sync (Only New Records)
```bash
# Sync only new records since last sync
php artisan sync:vps-all-tables
```

### 4. Skip Existing Records
```bash
# Skip records that already exist (faster)
php artisan sync:vps-all-tables --full --skip-existing
```

## What Gets Synced

### punch_logs
- All machine punch records (IN/OUT)
- Includes: employee_id, punch_date, punch_time, device_name, area_name, punch_state_char, verify_type_char
- Unique by: employee_id + punch_date + punch_time

### manual_attendances
- All manual attendance entries
- Includes: roll_number, punch_date, punch_time, state (IN/OUT), marked_by, notes
- Unique by: roll_number + punch_date + punch_time + state

### whatsapp_logs
- All WhatsApp notification logs
- Includes: roll_number, state, punch_date, punch_time, sent_at, status, error
- Unique by: roll_number + punch_date + punch_time + state

### notification_queue
- All notification queue entries
- Includes: roll_number, punch_date, punch_time, queued_at, processed_at, processed
- Unique by: roll_number + punch_date + punch_time

## How It Works

1. **Connects to VPS database** using the `vps` connection from `.env`
2. **Checks table existence** on both VPS and local
3. **Maps columns** - Only syncs columns that exist in both databases
4. **Handles duplicates** - Uses unique keys to prevent duplicate entries
5. **Tracks progress** - Stores sync state in `vps_sync_tracker` table
6. **Batch processing** - Processes 1000 records at a time to avoid memory issues

## Sync Tracker

The command maintains a `vps_sync_tracker` table that tracks:
- Last synced ID (for ID-based incremental sync)
- Last synced datetime (for date-based incremental sync)
- Total records synced

Each table has its own tracker entry.

## Full Sync vs Incremental Sync

### Full Sync (`--full`)
- Syncs **ALL** records from VPS
- Ignores previous sync state
- Use this for initial setup or when you want to refresh everything

### Incremental Sync (default)
- Only syncs **new** records since last sync
- Uses ID or date-based tracking
- Faster and more efficient for regular updates

## Examples

### Initial Setup (First Time)
```bash
# Sync everything from scratch
php artisan sync:vps-all-tables --full
```

### Daily Updates
```bash
# Sync only new records
php artisan sync:vps-all-tables
```

### Sync Specific Data
```bash
# Only sync manual attendance entries
php artisan sync:vps-all-tables --full --table=manual_attendances
```

## Troubleshooting

### "VPS database connection not configured"
- Check your `.env` file for `DB_VPS_*` settings
- Ensure VPS database credentials are correct

### "Table does not exist"
- The table might not exist on VPS or locally
- Run migrations: `php artisan migrate`
- Check if table exists on VPS server

### "No records to sync"
- All records are already synced
- Use `--full` to force re-sync
- Check sync tracker: `SELECT * FROM vps_sync_tracker;`

### Sync is slow
- Use `--skip-existing` to skip duplicate checks
- Reduce batch size in code (currently 1000)
- Check network connection to VPS

## Notes

- **Data is copied as-is** from VPS to local
- **Only matching columns** are synced (columns that exist in both databases)
- **Foreign keys** are preserved if they exist
- **Timestamps** are copied as-is
- **No data transformation** - exact copy from VPS

## Old Command (Still Available)

The old `sync:vps-punch-logs` command is still available for backward compatibility:
```bash
php artisan sync:vps-punch-logs --full
```

But it only syncs `punch_logs`. Use `sync:vps-all-tables` for comprehensive syncing.
