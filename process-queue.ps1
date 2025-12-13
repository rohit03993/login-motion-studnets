# Real-time Notification Queue Processor
# This script processes the notification queue that's populated by database triggers
# Messages are sent IMMEDIATELY when a punch is inserted (triggered by database)
# Press Ctrl+C to stop

Write-Host "Starting Notification Queue Processor..." -ForegroundColor Green
Write-Host "Processing queue every 1 second (triggered by database inserts)..." -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

cd "F:\Rohit Development\Attendance system"

php artisan queue:process-notifications --continuous --interval=1

