<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BlockedIpMiddleware
{
    /**
     * List of permanently blocked IPs (could be moved to database)
     */
    private array $permanentlyBlockedIps = [
        // Add known malicious IPs here
        // '192.168.1.100',
    ];

    /**
     * List of whitelisted IPs (admin IPs, office IPs, etc.)
     */
    private array $whitelistedIps = [
        '127.0.0.1',
        '::1',
        // Add your admin/office IPs here
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Always allow whitelisted IPs
        if (in_array($ip, $this->whitelistedIps)) {
            return $next($request);
        }

        // Check if IP is permanently blocked
        if (in_array($ip, $this->permanentlyBlockedIps)) {
            $this->logBlockedAttempt($request, 'permanently_blocked');

            return $this->createBlockedResponse('IP permanently blocked');
        }

        // Check if IP is temporarily blocked
        if (self::isIpBlocked($ip)) {
            $this->logBlockedAttempt($request, 'temporarily_blocked');

            return $this->createBlockedResponse('IP temporarily blocked due to suspicious activity');
        }

        // Check if IP is in suspicious activity list
        if ($this->isSuspiciousIp($ip)) {
            $this->logSuspiciousAttempt($request);
            // Don't block, but log for monitoring
        }

        return $next($request);
    }

    /**
     * Check if IP has suspicious activity
     */
    private function isSuspiciousIp(string $ip): bool
    {
        $suspiciousCount = Cache::get("suspicious_activity:ip:{$ip}", 0);

        return $suspiciousCount >= 3; // Flag as suspicious after 3 violations
    }

    /**
     * Log blocked access attempt
     */
    private function logBlockedAttempt(Request $request, string $blockType): void
    {
        Log::warning('Blocked IP access attempt', [
            'ip' => $request->ip(),
            'block_type' => $blockType,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'headers' => $this->getSafeHeaders($request),
        ]);
    }

    /**
     * Log suspicious access attempt
     */
    private function logSuspiciousAttempt(Request $request): void
    {
        Log::info('Suspicious IP access attempt', [
            'ip' => $request->ip(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Create blocked response
     */
    private function createBlockedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Access denied',
            'error' => 'access_forbidden',
            'code' => 403,
        ], 403, [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Get safe headers for logging (exclude sensitive ones)
     */
    private function getSafeHeaders(Request $request): array
    {
        $headers = $request->headers->all();

        // Remove sensitive headers from logs
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'set-cookie',
            'x-csrf-token',
            'x-api-key',
        ];

        foreach ($sensitiveHeaders as $header) {
            unset($headers[$header]);
        }

        return $headers;
    }

    /**
     * Manually block an IP address
     */
    public static function blockIp(string $ip, int $seconds = 3600, string $reason = 'Manual block'): void
    {
        Cache::put("blocked_ip:{$ip}", [
            'blocked_at' => now()->toISOString(),
            'reason' => $reason,
            'expires_at' => now()->addSeconds($seconds)->toISOString(),
        ], $seconds);

        Log::warning('IP manually blocked', [
            'ip' => $ip,
            'reason' => $reason,
            'duration_seconds' => $seconds,
            'blocked_by' => auth()->id() ?? 'system',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Unblock an IP address
     */
    public static function unblockIp(string $ip, string $reason = 'Manual unblock'): void
    {
        Cache::forget("blocked_ip:{$ip}");
        Cache::forget("suspicious_activity:ip:{$ip}");

        Log::info('IP manually unblocked', [
            'ip' => $ip,
            'reason' => $reason,
            'unblocked_by' => auth()->id() ?? 'system',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get blocked IP status
     */
    public static function getBlockedIpStatus(string $ip): ?array
    {
        return Cache::get("blocked_ip:{$ip}");
    }

    /**
     * Check if IP is blocked (static method for external use)
     */
    public static function isIpBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }
}
