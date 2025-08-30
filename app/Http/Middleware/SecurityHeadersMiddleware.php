<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Security headers configuration
     */
    private array $securityHeaders = [
        // Prevent clickjacking attacks
        'X-Frame-Options' => 'DENY',

        // Prevent MIME type sniffing
        'X-Content-Type-Options' => 'nosniff',

        // Enable XSS protection
        'X-XSS-Protection' => '1; mode=block',

        // Referrer policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // Feature policy (permissions)
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',

        // Remove server information
        'Server' => 'Secure-Server',

        // HSTS (HTTP Strict Transport Security) - only in production
        // 'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers
        foreach ($this->securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Content Security Policy - more restrictive for API
        if ($this->isApiRequest($request)) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'none'; script-src 'none'; object-src 'none'; base-uri 'none';"
            );
        } else {
            // CSP for web requests
            $csp = $this->buildContentSecurityPolicy($request);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // HSTS header only for HTTPS and production
        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Remove sensitive server headers
        $this->removeSensitiveHeaders($response);

        return $response;
    }

    /**
     * Check if request is API request
     */
    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Build Content Security Policy for web requests
     */
    private function buildContentSecurityPolicy(Request $request): string
    {
        $domain = $request->getHost();

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Adjust based on your needs
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'",
            "child-src 'none'",
            "worker-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "manifest-src 'self'",
        ]);
    }

    /**
     * Remove sensitive server headers
     */
    private function removeSensitiveHeaders(Response $response): void
    {
        $headersToRemove = [
            'X-Powered-By',
            'x-powered-by',
            'Server',
            'server',
        ];

        foreach ($headersToRemove as $header) {
            $response->headers->remove($header);
        }

        // Set secure server header
        $response->headers->set('Server', 'Secure-Server/1.0');
    }
}
