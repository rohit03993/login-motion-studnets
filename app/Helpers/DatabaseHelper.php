<?php

if (!function_exists('getPunchLogsConnection')) {
    /**
     * Get the database connection name to use for punch_logs table.
     * Returns 'vps' if VPS connection is enabled, otherwise returns 'mysql' (local).
     * 
     * This allows switching between:
     * - Local punch_logs table (when USE_VPS_FOR_PUNCH_LOGS=false)
     * - VPS punch_logs table (when USE_VPS_FOR_PUNCH_LOGS=true) for testing with live data
     * 
     * All other tables (students, employees, users, etc.) always use local database.
     * 
     * @return string Connection name ('vps' or 'mysql')
     */
    function getPunchLogsConnection(): string
    {
        // Check if VPS connection is enabled via .env flag
        $useVps = env('USE_VPS_FOR_PUNCH_LOGS', false);
        
        // Handle string 'true' or boolean true
        if ($useVps === true || $useVps === 'true' || $useVps === '1') {
            return 'vps'; // Use VPS database for punch_logs
        }
        
        return 'mysql'; // Use local database for punch_logs
    }
}
