# Laravel Scheduler Runner for Windows
# This script runs the scheduler every minute automatically
# Press Ctrl+C to stop

Write-Host "Starting Laravel Scheduler..." -ForegroundColor Green
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

while ($true) {
    php artisan schedule:run
    Start-Sleep -Seconds 60
}

