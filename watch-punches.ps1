# Real-time Punch Watcher for Windows
# This script continuously watches for new punches and sends WhatsApp messages immediately
# Press Ctrl+C to stop

Write-Host "Starting Real-time Punch Watcher..." -ForegroundColor Green
Write-Host "Checking for new punches every 10 seconds..." -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

cd "F:\Rohit Development\Attendance system"

php artisan punch:watch --interval=10

