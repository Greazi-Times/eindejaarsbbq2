<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRequestOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $origin = $request->headers->get('Origin');
        $fetchSite = $request->headers->get('Sec-Fetch-Site');

        if ($fetchSite === 'cross-site' || ($origin && ! $this->isAllowedOrigin($origin))) {
            abort(Response::HTTP_FORBIDDEN, 'Cross-origin request rejected.');
        }

        return $next($request);
    }

    private function isAllowedOrigin(string $origin): bool
    {
        $normalizedOrigin = $this->normalizeOrigin($origin);

        if ($normalizedOrigin === null) {
            return false;
        }

        foreach (config('security.allowed_origins', []) as $allowedOrigin) {
            if (hash_equals((string) $this->normalizeOrigin($allowedOrigin), $normalizedOrigin)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeOrigin(string $origin): ?string
    {
        $parts = parse_url(trim($origin));

        if (
            ! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])
            || (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/')
        ) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(rtrim($parts['host'], '.'));
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return "{$scheme}://{$host}:{$port}";
    }
}
