<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), display-capture=(), geolocation=(), microphone=(), payment=(), usb=()',
        );

        if (app()->isProduction()) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

            if ($request->isSecure()) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
            }
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "connect-src 'self'",
        ]);
    }
}
