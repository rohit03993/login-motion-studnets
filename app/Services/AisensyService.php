<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class AisensyService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        // Allow legacy env names (SENSY_*) as fallback
        $this->apiKey = env('AISENSY_API_KEY', env('SENSY_API_KEY', config('services.aisensy.api_key')));
        $this->apiUrl = Setting::get(
            'aisensy_url',
            env('AISENSY_URL', env('SENSY_API_URL', config('services.aisensy.url')))
        );
    }

    /**
     * Send WhatsApp message via Aisensy API
     * 
     * @param string $phone Phone number in +91XXXXXXXXXX format
     * @param array $templateParams Array of template variables (will be sent as templateParams)
     * @param string|null $templateName Template/campaign name (if null, uses default)
     * @return array ['status' => 'success'|'failed', 'error' => string|null, 'response' => array|null]
     */
    public function send(string $phone, array $templateParams, ?string $templateName = null): array
    {
        if (empty($this->apiKey) || empty($this->apiUrl)) {
            return ['status' => 'failed', 'error' => 'Aisensy API key or URL not configured.'];
        }

        // Normalize phone number to +91XXXXXXXXXX
        $phone = $this->normalizeIndianMobile($phone);
        if (!$phone) {
            return ['status' => 'failed', 'error' => 'Invalid Indian mobile number.'];
        }

        // Use provided template or default
        $campaignName = $templateName ?? config('services.aisensy.template', 'ATTENDANCE_ALERT');

        // If the URL does not include a path, append Aisensy API path
        $url = $this->apiUrl;
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === '' || $path === '/') {
            // Default documented API path
            $url = rtrim($url, '/') . '/campaign/t1/api/v2';
        }

        // Sanitize userName to meet Aisensy constraints
        $userName = $this->sanitizeUserName($templateParams[0] ?? 'Student');

        // Aisensy API payload format
        $payload = [
            'apiKey' => $this->apiKey,
            'campaignName' => $campaignName,
            'destination' => $phone,
            'userName' => $userName, // sanitized name
            'templateParams' => $templateParams,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $responseData = $response->json();
            
            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'response' => $responseData,
                ];
            }

            return [
                'status' => 'failed',
                'error' => $responseData['message'] ?? $response->body(),
                'response' => $responseData,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize Indian mobile numbers to +91XXXXXXXXXX format
     * Accepts: 10-digit, 12-digit starting with 91, or +91 formats
     */
    private function normalizeIndianMobile(string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-digit characters
        $digits = preg_replace('/\D+/', '', $phone);

        // 10-digit local number
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }

        // 12-digit with leading 91
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }

        // Already in +91 format (13 digits after stripping)
        if (strlen($digits) === 12 && str_starts_with($phone, '+91')) {
            return '+91' . substr($digits, 2);
        }

        // If already has +91 prefix, return as is
        if (str_starts_with($phone, '+91') && strlen($digits) === 12) {
            return $phone;
        }

        return null;
    }

    /**
     * Sanitize userName to meet Aisensy constraints (alphanumeric and spaces), length capped.
     */
    private function sanitizeUserName(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 ]+/', '', $name) ?? 'Student';
        $clean = trim($clean);
        if ($clean === '') {
            $clean = 'Student';
        }
        // Limit length (e.g., 20 chars)
        return mb_substr($clean, 0, 20);
    }
}

