<?php

$appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST);

$csv = static fn (?string $value): array => array_values(array_filter(array_map(
    static fn (string $item): string => trim($item),
    explode(',', (string) $value),
)));

$trustedHosts = array_map(
    static fn (string $host): string => '^'.preg_quote(strtolower(rtrim($host, '.')), '/').'$',
    $csv(env('TRUSTED_HOSTS')),
);

return [
    /*
    |--------------------------------------------------------------------------
    | Hosts and browser origins
    |--------------------------------------------------------------------------
    |
    | Host validation prevents forged Host headers. Origin validation rejects
    | cross-site browser requests that try to change application state.
    |
    */
    'trusted_hosts' => $trustedHosts ?: array_values(array_filter([
        $appHost ? '^'.preg_quote($appHost, '/').'$' : null,
    ])),

    'allowed_origins' => $csv(env('ALLOWED_ORIGINS')) ?: [
        rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted reverse proxies
    |--------------------------------------------------------------------------
    |
    | Leave empty when Nginx connects directly to PHP-FPM. Only add proxy IPs
    | or CIDR ranges that you control; never use "*" on a public server.
    |
    */
    'trusted_proxies' => $csv(env('TRUSTED_PROXIES')),
];
