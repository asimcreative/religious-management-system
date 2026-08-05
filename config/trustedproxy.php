<?php

return [
    // Configure only reverse proxies controlled by the deployment. The Docker
    // template trusts its private Nginx-to-PHP-FPM hop via REMOTE_ADDR.
    'proxies' => env('TRUSTED_PROXIES'),
];
