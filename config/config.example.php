<?php
// Copy to config/config.php and fill in. config.php is git-ignored (secrets never committed).
return [
    'db' => [
        'dsn'      => 'pgsql:host=db.fe.up.pt;port=5432;dbname=YOUR_DB',
        'user'     => 'YOUR_USER',
        'password' => 'YOUR_PASSWORD',
        'schema'   => 'public',          // FEUP: use your own schema name if you created one
    ],
    'db_test' => null,                   // tests only, e.g. same shape as 'db' pointing to trailsense_test
    'base_url'         => '/~upXXXXXXXXX/trailsense/public/',
    'weather_provider' => 'open-meteo',  // 'open-meteo' in production; 'demo' only for offline local demos (ADR-004)
    'debug'            => false,
    'timezone'         => 'Europe/Lisbon',
];
