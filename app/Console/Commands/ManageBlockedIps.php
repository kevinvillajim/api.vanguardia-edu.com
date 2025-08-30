<?php

namespace App\Console\Commands;

use App\Http\Middleware\BlockedIpMiddleware;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ManageBlockedIps extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:ip 
                            {action : The action to perform (list|block|unblock|status)}
                            {ip? : The IP address to manage}
                            {--duration=3600 : Duration in seconds for blocking (default: 1 hour)}
                            {--reason=Manual block : Reason for blocking}';

    /**
     * The console command description.
     */
    protected $description = 'Manage blocked IP addresses for security';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $ip = $this->argument('ip');

        return match ($action) {
            'list' => $this->listBlockedIps(),
            'block' => $this->blockIp($ip),
            'unblock' => $this->unblockIp($ip),
            'status' => $this->checkStatus($ip),
            default => $this->showHelp()
        };
    }

    /**
     * List all currently blocked IPs
     */
    private function listBlockedIps(): int
    {
        $this->info('Scanning for blocked IP addresses...');

        // Get all cache keys (this is a simplified approach)
        // In production, you'd want a more efficient way to track blocked IPs
        $blockedIps = $this->getBlockedIpsFromCache();

        if (empty($blockedIps)) {
            $this->info('No IP addresses are currently blocked.');

            return 0;
        }

        $this->table(
            ['IP Address', 'Blocked At', 'Expires At', 'Reason'],
            array_map(function ($ip, $data) {
                return [
                    $ip,
                    $data['blocked_at'] ?? 'Unknown',
                    $data['expires_at'] ?? 'Unknown',
                    $data['reason'] ?? 'No reason specified',
                ];
            }, array_keys($blockedIps), $blockedIps)
        );

        return 0;
    }

    /**
     * Block an IP address
     */
    private function blockIp(?string $ip): int
    {
        if (! $ip) {
            $this->error('IP address is required for blocking.');

            return 1;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address format.');

            return 1;
        }

        $duration = (int) $this->option('duration');
        $reason = $this->option('reason');

        // Check if already blocked
        if (BlockedIpMiddleware::isIpBlocked($ip)) {
            $this->warn("IP {$ip} is already blocked.");

            return 0;
        }

        // Confirm blocking
        if (! $this->confirm("Are you sure you want to block IP {$ip} for {$duration} seconds?")) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Block the IP
        BlockedIpMiddleware::blockIp($ip, $duration, $reason);

        $this->info("Successfully blocked IP {$ip} for {$duration} seconds.");
        $this->info("Reason: {$reason}");

        return 0;
    }

    /**
     * Unblock an IP address
     */
    private function unblockIp(?string $ip): int
    {
        if (! $ip) {
            $this->error('IP address is required for unblocking.');

            return 1;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address format.');

            return 1;
        }

        // Check if actually blocked
        if (! BlockedIpMiddleware::isIpBlocked($ip)) {
            $this->warn("IP {$ip} is not currently blocked.");

            return 0;
        }

        // Confirm unblocking
        if (! $this->confirm("Are you sure you want to unblock IP {$ip}?")) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Unblock the IP
        BlockedIpMiddleware::unblockIp($ip, 'Manual unblock via console');

        $this->info("Successfully unblocked IP {$ip}.");

        return 0;
    }

    /**
     * Check status of an IP address
     */
    private function checkStatus(?string $ip): int
    {
        if (! $ip) {
            $this->error('IP address is required for status check.');

            return 1;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address format.');

            return 1;
        }

        $this->info("Checking status for IP: {$ip}");

        // Check if blocked
        if (BlockedIpMiddleware::isIpBlocked($ip)) {
            $status = BlockedIpMiddleware::getBlockedIpStatus($ip);

            $this->table(
                ['Property', 'Value'],
                [
                    ['Status', '🔴 BLOCKED'],
                    ['Blocked At', $status['blocked_at'] ?? 'Unknown'],
                    ['Expires At', $status['expires_at'] ?? 'Unknown'],
                    ['Reason', $status['reason'] ?? 'No reason specified'],
                ]
            );
        } else {
            $this->info('🟢 IP is not blocked');
        }

        // Check suspicious activity
        $suspiciousCount = Cache::get("suspicious_activity:ip:{$ip}", 0);
        if ($suspiciousCount > 0) {
            $this->warn("⚠️  This IP has {$suspiciousCount} suspicious activity entries");
        }

        // Check rate limiting
        $rateLimitKeys = [
            'auth.login',
            'auth.register',
            'api.general',
        ];

        foreach ($rateLimitKeys as $limitType) {
            $key = "rate_limit:{$limitType}:ip:{$ip}";
            $attempts = Cache::get($key, 0);
            if ($attempts > 0) {
                $this->line("Rate limit ({$limitType}): {$attempts} attempts");
            }
        }

        return 0;
    }

    /**
     * Show help information
     */
    private function showHelp(): int
    {
        $this->error('Invalid action. Available actions: list, block, unblock, status');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan security:ip list');
        $this->line('  php artisan security:ip block 192.168.1.100 --duration=7200 --reason="Suspicious activity"');
        $this->line('  php artisan security:ip unblock 192.168.1.100');
        $this->line('  php artisan security:ip status 192.168.1.100');

        return 1;
    }

    /**
     * Get blocked IPs from cache (simplified implementation)
     */
    private function getBlockedIpsFromCache(): array
    {
        // This is a simplified approach. In production, you'd want to maintain
        // a separate index of blocked IPs for efficient querying
        $blockedIps = [];

        // For demonstration, we'll check some common IP patterns
        // In reality, you'd maintain a proper index
        for ($i = 1; $i <= 255; $i++) {
            $testIp = "192.168.1.{$i}";
            if (BlockedIpMiddleware::isIpBlocked($testIp)) {
                $blockedIps[$testIp] = BlockedIpMiddleware::getBlockedIpStatus($testIp);
            }
        }

        // Also check some common external IPs that might be blocked
        $commonIps = [
            '127.0.0.1', '10.0.0.1', '172.16.0.1', '8.8.8.8',
        ];

        foreach ($commonIps as $testIp) {
            if (BlockedIpMiddleware::isIpBlocked($testIp)) {
                $blockedIps[$testIp] = BlockedIpMiddleware::getBlockedIpStatus($testIp);
            }
        }

        return $blockedIps;
    }
}
