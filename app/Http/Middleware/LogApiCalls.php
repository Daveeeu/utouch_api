<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class LogApiCalls
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Process the request
        $response = $next($request);

        // Log only API routes
        if (strpos($request->getPathInfo(), '/api/') === 0) {
            $this->logApiCall($request, $response);
        }

        return $response;
    }

    /**
     * Log the API call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function logApiCall(Request $request, $response)
    {
        // Get authenticated user if available
        $user = $request->user();

        // Create log entry
        activity('api')
            ->causedBy($user)
            ->withProperties([
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'request_data' => $this->filterSensitiveData($request->all()),
                'response_status' => $response->getStatusCode(),
                'response_data' => $this->getResponseData($response),
                'duration' => defined('LARAVEL_START') ? round((microtime(true) - LARAVEL_START) * 1000, 2) : null,
            ])
            ->log($request->method() . ' ' . $request->getPathInfo());
    }

    /**
     * Filter out sensitive data from the request.
     *
     * @param  array  $data
     * @return array
     */
    protected function filterSensitiveData(array $data): array
    {
        // Define keys that contain sensitive information
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'api_key', 'credit_card'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            } elseif (is_string($value) && $this->isSensitiveKey($key, $sensitiveKeys)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }

    /**
     * Check if the key contains sensitive information.
     *
     * @param  string  $key
     * @param  array  $sensitiveKeys
     * @return bool
     */
    protected function isSensitiveKey(string $key, array $sensitiveKeys): bool
    {
        $lowercaseKey = strtolower($key);

        foreach ($sensitiveKeys as $sensitiveKey) {
            if (strpos($lowercaseKey, $sensitiveKey) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get response data for logging.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return mixed
     */
    protected function getResponseData($response)
    {
        // Only attempt to decode JSON responses
        $content = $response->getContent();

        // Limit response data size to prevent huge logs
        if (strlen($content) > 10000) {
            return '[Response content too large to log]';
        }

        if ($this->isJsonResponse($response)) {
            try {
                return json_decode($content, true);
            } catch (\Exception $e) {
                return $content;
            }
        }

        return '[Non-JSON response]';
    }

    /**
     * Check if the response is JSON.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function isJsonResponse($response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        return $contentType && strpos($contentType, 'application/json') !== false;
    }
}
