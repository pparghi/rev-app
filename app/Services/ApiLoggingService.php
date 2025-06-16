<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApiLoggingService
{
    public function logApiCall(string $apiName, array $request, $response, string $status = 'success', ?string $userId = null)
    {
        $logData = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'api_name' => $apiName,
            'request' => $request,
            'response' => $response,
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_id' => $userId ?? 'UNKNOWN',
        ];

        // Create directory if it doesn't exist
        if (!Storage::exists('api_logs')) {
            Storage::makeDirectory('api_logs');
        }

        // Log to daily file
        Storage::append('api_logs/api_' . now()->format('Y-m-d') . '.log', json_encode($logData));
        
        // Also log to Laravel's default logging system
        Log::info($apiName . ' called', $logData);
    }
}