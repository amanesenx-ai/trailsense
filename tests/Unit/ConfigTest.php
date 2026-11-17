<?php
declare(strict_types=1);

return [
    'DATABASE_URL is turned into a PDO DSN with SSL by default (cloud hosting)' => function () {
        putenv('DATABASE_URL=postgresql://amanesenx:p%40ss@postgresql-amanesenx.alwaysdata.net:5432/amanesenx_trailsense');
        putenv('DB_PASSWORD');
        $c = config_from_env();
        assertSame('pgsql:host=postgresql-amanesenx.alwaysdata.net;port=5432;dbname=amanesenx_trailsense;sslmode=require', $c['db']['dsn']);
        assertSame('amanesenx', $c['db']['user']);
        assertSame('p@ss', $c['db']['password']);
        assertSame('open-meteo', $c['weather_provider'], 'production default never uses demo weather');
        assertSame(false, $c['debug']);
    },
    'DB_PASSWORD overrides the password in the URL' => function () {
        putenv('DATABASE_URL=postgresql://u@host/db?sslmode=prefer');
        putenv('DB_PASSWORD=secret-from-env');
        $c = config_from_env();
        assertSame('secret-from-env', $c['db']['password']);
        assertContains('sslmode=prefer', $c['db']['dsn']);
        putenv('DATABASE_URL');
        putenv('DB_PASSWORD');
    },
];
