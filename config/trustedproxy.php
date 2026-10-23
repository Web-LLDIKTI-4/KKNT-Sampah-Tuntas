<?php

return [
    // Reverse proxy IPs (comma separated) read by Laravel's TrustProxies; empty = trust none. Wildcards are dropped on purpose.
    'proxies' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))),
        fn (string $ip) => $ip !== '' && ! str_contains($ip, '*'),
    )),
];
