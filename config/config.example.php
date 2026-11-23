<?php
// Copy to config/config.php and fill in. config.php is git-ignored (secrets never committed).
return [
    'db' => [
        'dsn'      => 'pgsql:host=localhost;port=5432;dbname=trailsense',
        'user'     => 'YOUR_USER',
        'password' => 'YOUR_PASSWORD',
        'schema'   => 'public',
    ],
    'db_test' => null,                   // tests only, e.g. same shape as 'db' pointing to trailsense_test
    'base_url'         => '/',
    'weather_provider' => 'open-meteo',  // 'open-meteo' in production; 'demo' only for offline local demos (ADR-004)
    'debug'            => false,
    'timezone'         => 'Europe/Lisbon',
];
