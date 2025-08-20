<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AdvancedRateLimitMiddleware
{
    /**
     * Rate limit configuration per endpoint
     */
    private array $rateLimits = [
        // Authentication endpoints - increased for testing
        'auth.login' => ['attempts' => 50, 'decay' => 900], // 50 attempts per 15 minutes
        'auth.register' => ['attempts' => 10, 'decay' => 3600], // 10 attempts per hour
        'auth.password-reset' => ['attempts' => 10, 'decay' => 3600],

        // API endpoints - increased limits for testing
        'api.general' => ['attempts' => 500, 'decay' => 3600], // 500 requests per hour
        'api.search' => ['attempts' => 50, 'decay' => 3600], // 50 searches per hour
        'api.upload' => ['attempts' => 10, 'decay' => 3600], // 10 uploads per hour

        // Admin endpoints - higher limits
        'admin.general' => ['attempts' => 500, 'decay' => 3600], // 500 requests per hour

        // Guest endpoints - very strict
        'guest.general' => ['attempts' => 20, 'decay' => 3600], // 20 requests per hour
    ];

    public function handle(Request $request, Closure $next, string $limitType = 'api.general'): Response
    {
        $user = $request->user();
        $ip = $request->ip();

        // Get rate limit configuration
        $config = $this->rateLimits[$limitType] ?? $this->rateLimits['api.general'];

        // Create composite key for IP + User + Endpoint
        $key = $this->createRateLimitKey($ip, $user?->id, $limitType);

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, $config['attempts'])) {
            $retryAfter = RateLimiter::availableIn($key);

            // Log rate limit violation
            $this->logRateLimitViolation($request, $limitType, $ip, $user?->id, $retryAfter);

            // Check for suspicious behavior
            $this->checkSuspiciousBehavior($ip, $user?->id);

            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded',
                'error' => 'rate_limit_exceeded',
                'retry_after' => $retryAfter,
                'limit_type' => $limitType,
            ], 429, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $config['attempts'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
        }

        // Increment attempt counter
        RateLimiter::hit($key, $config['decay']);

        // Get remaining attempts
        $remaining = $config['attempts'] - RateLimiter::attempts($key);

        // Process request
        $response = $next($request);

        // Add rate limit headers to response
        $response->headers->add([
            'X-RateLimit-Limit' => $config['attempts'],
            'X-RateLimit-Remaining' => max(0, $remaining - 1),
            'X-RateLimit-Reset' => now()->addSeconds($config['decay'])->timestamp,
        ]);

        return $response;
    }

    /**
     * Create unique rate limit key
     */
    private function createRateLimitKey(string $ip, ?int $userId, string $limitType): string
    {
        $identifier = $userId ? "user:{$userId}" : "ip:{$ip}";

        return "rate_limit:{$limitType}:{$identifier}";
    }

    /**
     * Log rate limit violation
     */
    private function logRateLimitViolation(Request $request, string $limitType, string $ip, ?int $userId, int $retryAfter): void
    {
        Log::warning('Rate limit exceeded', [
            'limit_type' => $limitType,
            'ip' => $ip,
            'user_id' => $userId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'retry_after' => $retryAfter,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check for suspicious behavior patterns
     */
    private function checkSuspiciousBehavior(string $ip, ?int $userId): void
    {
        $suspiciousKey = 'suspicious_activity:'.($userId ? "user:{$userId}" : "ip:{$ip}");

        // Increment suspicious activity counter
        $violations = Cache::increment($suspiciousKey, 1);

        // Set expiry for the counter (24 hours)
        if ($violations === 1) {
            Cache::expire($suspiciousKey, 86400);
        }

        // If too many violations, flag as suspicious
        if ($violations >= 5) {
            $this->flagSuspiciousActivity($ip, $userId, $violations);
        }
    }

    /**
     * Flag suspicious activity
     */
    private function flagSuspiciousActivity(string $ip, ?int $userId, int $violations): void
    {
        // Log high-priority security alert
        Log::critical('Suspicious activity detected', [
            'ip' => $ip,
            'user_id' => $userId,
            'violations_count' => $violations,
            'timestamp' => now()->toISOString(),
            'action_required' => true,
        ]);

        // Optionally implement automatic IP blocking
        if ($violations >= 10) {
            $this->blockIpAddress($ip, 3600); // Block for 1 hour
        }
    }

    /**
     * Block IP address temporarily
     */
    private function blockIpAddress(string $ip, int $seconds): void
    {
        Cache::put("blocked_ip:{$ip}", true, $seconds);

        Log::emergency('IP address blocked due to suspicious activity', [
            'ip' => $ip,
            'blocked_for_seconds' => $seconds,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
